<?php

namespace App\Service;

use App\Entity\CatalogSearchResponse;
use App\Service\LoanMonitor\LoanMonitorClient;
use BCLib\PrimoClient\ApiClient;
use BCLib\PrimoClient\Doc;
use BCLib\PrimoClient\Exceptions\BadAPIResponseException;
use BCLib\PrimoClient\Holding;
use BCLib\PrimoClient\Item;
use BCLib\PrimoClient\Query;
use BCLib\PrimoClient\QueryConfig;
use BCLib\PrimoClient\SearchRequest;
use BCLib\PrimoClient\SearchResponse;
use BCLib\PrimoClient\SearchTranslator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\CacheItem;

/**
 * Search Primo
 *
 * Primo searches are used to populate three bento boxes:
 *
 *     * catalog (books)
 *     * articles
 *     * videos
 *
 * Each service has its own configuration. All three configurations are inserted when the
 * object is constructed.
 *
 * TODO Should we refactor Primo search to multiple services? It's very big.
 *
 * @package App\Service
 */
class PrimoSearch implements LoggerAwareInterface
{
    /** @var QueryConfig */
    private $books_query_config;

    /** @var QueryConfig */
    private $online_query_config;

    /** @var QueryConfig */
    private $video_query_config;

    /** @var QueryConfig */
    private $article_query_config;

    /** @var ApiClient */
    private $client;

    /** @var AlmaClient */
    private $alma;

    /** @var VideoThumbService */
    private $video_thumbs;

    /** @var AdapterInterface */
    private $cache;

    // Expire search results after twelve hours (in seconds)
    private const CACHE_LIFETIME = 60 * 60 * 12;

    private const CACHED_SEARCH_RESULT_TAG = 'catalog_search_result';

    /** @var LoggerInterface */
    private $logger;

    /**
     * Maps types from PNX records to display names
     */
    private const TYPE_MAP = [
        'book'                => 'Book',
        'video'               => 'Video',
        'journal'             => 'Journal',
        'government_document' => 'Government document',
        'database'            => 'Database',
        'image'               => 'Image',
        'audio_music'         => 'Musical recording',
        'audio_other'         => 'Audio',
        'realia'              => '',
        'data'                => 'Data',
        'dissertation'        => 'Thesis',
        'article'             => 'Article',
        'review'              => 'Review',
        'reference_entry'     => 'Reference entry',
        'newspaper_article'   => 'Newspaper article',
        'other'               => ''
    ];

    public function __construct(
        QueryConfig       $books_query_config,
        QueryConfig       $video_query_config,
        QueryConfig       $article_query_config,
        QueryConfig       $online_query_config,
        ApiClient         $client,
        AlmaClient        $alma,
        VideoThumbService $video_thumbs,
        AdapterInterface  $cache,
        LoanMonitorClient $loan_monitor
    )
    {
        $this->books_query_config = $books_query_config;
        $this->video_query_config = $video_query_config;
        $this->article_query_config = $article_query_config;
        $this->client = $client;
        $this->alma = $alma;

        $this->cache = new TagAwareAdapter($cache);

        // Save thumbnail service and add providers we need.
        $this->video_thumbs = $video_thumbs;
        $this->video_thumbs->addProvider(new MediciTVScreencapProvider(new Client()));
        $this->video_thumbs->addProvider(new MetOnDemandScreencapProvider(new Client()));
        $this->online_query_config = $online_query_config;
    }

    /**
     * Search the full catalog
     *
     * @param string $keyword
     * @param int $limit
     * @return CatalogSearchResponse
     * @throws BadAPIResponseException
     * @throws GuzzleException
     * @throws InvalidArgumentException
     */
    public function searchFullCatalog(string $keyword, int $limit): CatalogSearchResponse
    {
        $result = $this->search($keyword, $limit + 1, $this->books_query_config, false);
        $search_url = $this->buildPrimoSearchUrl($keyword, 'bcl_only', 'bcl');
        $result->setSearchUrl($search_url);
        $hacked_docs = PrimoResultHacks::runHacks($result->getDocs());
        $result->setDocs(array_slice($hacked_docs, 0, $limit));
        return $result;
    }

    /**
     * Search only online resources
     *
     * @param string $keyword
     * @param int $limit
     * @return CatalogSearchResponse
     * @throws BadAPIResponseException
     * @throws GuzzleException
     * @throws InvalidArgumentException
     */
    public function searchOnlineResources(string $keyword, int $limit): CatalogSearchResponse
    {
        $result = $this->search($keyword, $limit + 1, $this->online_query_config, false);
        $search_url = $this->buildPrimoSearchUrl($keyword, 'online', 'online');
        $result->setSearchUrl($search_url);
        $hacked_docs = PrimoResultHacks::runHacks($result->getDocs());
        $result->setDocs(array_slice($hacked_docs, 0, $limit));
        return $result;
    }

    /**
     * Search video service
     *
     * @param string $keyword
     * @param int $limit
     * @return CatalogSearchResponse
     * @throws BadAPIResponseException
     * @throws GuzzleException
     * @throws InvalidArgumentException
     */
    public function searchVideo(string $keyword, int $limit): CatalogSearchResponse
    {
        $result = $this->search($keyword, $limit, $this->video_query_config, false);
        $search_url = $this->buildPrimoSearchUrl($keyword, 'video', 'VIDEO');
        $result->setSearchUrl($search_url);
        return $result;
    }

    /**
     * Search for articles
     *
     * @param string $keyword
     * @param int $limit
     * @return CatalogSearchResponse
     * @throws BadAPIResponseException
     * @throws GuzzleException
     * @throws InvalidArgumentException
     */
    public function searchArticle(string $keyword, int $limit): CatalogSearchResponse
    {
        $result = $this->search($keyword, $limit, $this->article_query_config, true);
        $search_url = $this->buildPrimoSearchUrl($keyword, 'pci_only', 'pci', 'false');
        $result->setSearchUrl($search_url);
        return $result;
    }

    /**
     * Perform the search
     *
     * @param string $keyword
     * @param int $limit
     * @param QueryConfig $config
     * @param bool $is_pci
     * @return CatalogSearchResponse
     * @throws BadAPIResponseException
     * @throws GuzzleException
     * @throws InvalidArgumentException
     */
    private function search(string $keyword, int $limit, QueryConfig $config, bool $is_pci): CatalogSearchResponse
    {
        // Filter out characters Primo doesn't like.
        $keyword = str_replace(array(':', ';', ',', '(', ')', '\\'), ' ', $keyword);

        // First check cache for search result.
        try {
            $cache_item = $this->cache->getItem($this->cacheKey($keyword, $limit, $config));
        } catch (InvalidArgumentException $e) {
            // If cache fails, build result directly from Primo and log the error.
            $this->logger->error("Primo cache error: {$e->getMessage()}\n{$e->getTraceAsString()}");
            $result = $this->buildPrimoResult($keyword, $limit, $config, $is_pci);
            $this->updateRealTimeAvailability($result);
            return $result;
        }

        if ($cache_item->isHit()) {
            $result = $cache_item->get();
        } else {

            // No cache hit? Query Primo.
            $result = $this->buildPrimoResult($keyword, $limit, $config, $is_pci);
            $this->cacheResult($cache_item, $result);
        }

        // Get current availability of physical items.
        $this->updateRealTimeAvailability($result);

        return $result;
    }

    /**
     * Update holdings records to reflect current availability
     *
     * Holdings records from Primo API Bib lookups contain availability information that can be out-of-date by up to
     * 24 hours. To get current availability information for non-electronic records we need to send a Real Time
     * Availability (RTA) request to Alma and reconcile those results with the holdings records.
     *
     * @param SearchResponse $results
     */
    protected function updateRealTimeAvailability(SearchResponse $results): void
    {
        $physical_docs = array_filter($results->getDocs(), function (Doc $doc) {
            return $doc->isPhysical();
        });

        $mmses = $this->getMMSIds($physical_docs);


        $holding_ids = $this->getHoldingIdsForRTA($physical_docs);
        $items = $this->alma->checkAvailability($holding_ids);
        $this->reconcileRealTimeAvailability($physical_docs, $items);
    }

    /**
     * @param Doc[] $physical_docs
     * @return string[]
     */
    protected function getMMSIds(array $physical_docs): array
    {
        $mmses = [];
        foreach ($physical_docs as $doc) {
            $mmses = array_merge($doc->pnx('display', 'lds11'), $mmses);
        }
        return $mmses;
    }

    /**
     * Populate a holding with values taken from a real-time lookup
     *
     * @param Holding $holding
     * @param Item $item
     */
    protected function populateHolding(Holding $holding, Item $item): void
    {
        $holding->setAvailabilityStatus($item->getAvailability());
        $holding->setLocationCode($item->getLocationCode());
        $holding->setLocationDisplay($item->getLocation());
        $holding->setLibraryCode($item->getLibrary());
    }

    /**
     * Build array of holding IDs to check for real-time-availability
     *
     * @param array $physical_docs
     * @return Doc[]
     */
    protected function getHoldingIdsForRTA(array $physical_docs): array
    {
        $holding_ids = [];
        foreach ($physical_docs as $doc) {
            foreach ($doc->getHoldings() as $holding) {
                $holding_ids[] = $holding->getIlsId();
            }
        }
        return $holding_ids;
    }

    /**
     * Reconcile Doc holding records with Real Time Availability
     *
     * @param Doc[] $physical_docs
     * @param Item[][] $items
     */
    protected function reconcileRealTimeAvailability(array $physical_docs, array $items): void
    {
        foreach ($physical_docs as $doc) {

            // By default set to unavailable
            $doc->setAvailable(false);


            foreach ($doc->getHoldings() as $holding) {

                // If there is one available holding, the item is available
                if ($holding->getAvailabilityStatus() === 'available') {
                    $doc->setAvailable(true);
                }

                // Fill the holding record
                $holding_id = $holding->getIlsId();
                if (isset($items[$holding_id])) {
                    $this->populateHolding($holding, $items[$holding_id][0]);

                    if ($items[$holding_id][0]->getAvailability() === 'available') {
                        $doc->setAvailable(true);
                    }

                    foreach ($items[$holding_id] as $item) {
                        $holding->addItem($item);
                    }
                }
            }
        }
    }

    /**
     * Map the type code to a display string
     *
     * @param Doc $doc
     * @return mixed|string
     */
    protected function displayType(Doc $doc)
    {
        return self::TYPE_MAP[$doc->getType()] ?? ucfirst($doc->getType());
    }

    /**
     * Build the cache key
     *
     * @param string $keyword
     * @param string $limit
     * @param QueryConfig $config
     * @return string
     */
    protected function cacheKey(string $keyword, string $limit, QueryConfig $config): string
    {
        $keyword = trim($keyword);
        $keyword = str_replace(array(':', ';', ',', '.', '(', ')', '/', '\\'), ' ', $keyword);
        $key = md5("$keyword-$limit-{$config->scope}");
        return "bcbento-catalog-search_$key";
    }

    /**
     * Send a search query to Ex Libris
     *
     * @param string $keyword
     * @param int $limit
     * @param QueryConfig $config
     * @return CatalogSearchResponse
     * @throws BadAPIResponseException
     * @throws GuzzleException
     */
    private function sendSearchQuery(string $keyword, int $limit, QueryConfig $config): CatalogSearchResponse
    {
        $query = new Query(Query::FIELD_ANY, Query::PRECISION_CONTAINS, $keyword);
        $request = new SearchRequest($config, $query);
        $request->limit($limit);
        if ($config->scope === 'pci') {
            $request->pcAvailability(false);
        }

        $query_url = $request->url();
        $this->logger->info("sending Primo query: $query_url");
        $json = $this->client->get($query_url);
        return new CatalogSearchResponse(SearchTranslator::translate($json));
    }

    private function buildPrimoSearchUrl(
        string $keyword,
        string $tab,
        string $scope,
        string $pcAvailability = null
    ): string
    {
        $extra = isset($pcAvailability) ? '&pcAvailability=false' : '';
        $keyword = urlencode($keyword);
        return "https://bc-primo.hosted.exlibrisgroup.com/primo-explore/search?query=any,contains,$keyword&tab=$tab&search_scope=$scope&vid=bclib_new&lang=en_US&offset=0$extra";
    }

    /**
     * Cache the search result
     *
     * @param CacheItem $cache_item
     * @param CatalogSearchResponse $result
     */
    private function cacheResult(CacheItem $cache_item, CatalogSearchResponse $result): void
    {
        try {
            $cache_item->set($result);
            $cache_item->expiresAfter(self::CACHE_LIFETIME);
            $cache_item->tag(self::CACHED_SEARCH_RESULT_TAG);
            $this->cache->save($cache_item);
        } catch (InvalidArgumentException $e) {
            $this->logger->error("Primo cache error: {$e->getMessage()}\n{$e->getTraceAsString()}");
        } catch (CacheException $e) {
            $this->logger->error("Primo cache error: {$e->getMessage()}\n{$e->getTraceAsString()}");
        }
    }

    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param string $keyword
     * @param int $limit
     * @param QueryConfig $config
     * @param bool $is_pci
     * @return CatalogSearchResponse
     * @throws InvalidArgumentException
     * @throws BadAPIResponseException
     * @throws GuzzleException
     */
    private function buildPrimoResult(
        string      $keyword,
        int         $limit,
        QueryConfig $config,
        bool        $is_pci
    ): CatalogSearchResponse
    {
        $result = $this->sendSearchQuery($keyword, $limit, $config);


        // Non-Primo Central results have extra fields
        if (!$is_pci) {

            // Load display types
            foreach ($result->getDocs() as $doc) {
                $doc->setType($this->displayType($doc));
            }

            // Load video thumbs
            $this->video_thumbs->fetch($result);
        }

        return $result;
    }
}
