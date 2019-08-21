<?php

namespace App\Service;

use App\Entity\CatalogSearchResponse;
use BCLib\PrimoClient\ApiClient;
use BCLib\PrimoClient\Doc;
use BCLib\PrimoClient\Holding;
use BCLib\PrimoClient\Item;
use BCLib\PrimoClient\Query;
use BCLib\PrimoClient\QueryConfig;
use BCLib\PrimoClient\SearchRequest;
use BCLib\PrimoClient\SearchResponse;
use BCLib\PrimoClient\SearchTranslator;
use GuzzleHttp\Client;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

class PrimoSearch
{
    /**
     * @var QueryConfig
     */
    private $books_query_config;

    /**
     * @var QueryConfig
     */
    private $video_query_config;

    /**
     * @var QueryConfig
     */
    private $article_query_config;

    /**
     * @var ApiClient
     */
    private $client;

    /**
     * @var AlmaClient
     */
    private $alma;

    /**
     * @var VideoThumbService
     */
    private $video_thumbs;

    /**
     * @var AdapterInterface
     */
    private $cache;

    // Expire search results after three hours
    private const CACHE_LIFETIME = 60 * 60 * 3;

    private const CACHED_SEARCH_RESULT_TAG = 'catalog_search_result';

    const TYPE_MAP = [
        'book' => 'Book',
        'video' => 'Video',
        'journal' => 'Journal',
        'government_document' => 'Government document',
        'database' => 'Database',
        'image' => 'Image',
        'audio_music' => 'Musical recording',
        'realia' => '',
        'data' => 'Data',
        'dissertation' => 'Thesis',
        'article' => 'Article',
        'review' => 'Review',
        'reference_entry' => 'Reference entry',
        'newspaper_article' => 'Newspaper article',
        'other' => ''
    ];

    public function __construct(
        QueryConfig $books_query_config,
        QueryConfig $video_query_config,
        QueryConfig $article_query_config,
        ApiClient $client,
        AlmaClient $alma,
        VideoThumbService $video_thumbs,
        AdapterInterface $cache
    ) {
        $this->books_query_config = $books_query_config;
        $this->video_query_config = $video_query_config;
        $this->article_query_config = $article_query_config;
        $this->client = $client;
        $this->alma = $alma;

        $this->video_thumbs = $video_thumbs;
        $this->video_thumbs->addProvider(new MediciTVVideoProvider(new Client()));
        $this->video_thumbs->addProvider(new MetOnDemandVideoProvider(new Client()));
        $this->cache = new TagAwareAdapter($cache);
    }

    public function searchFullCatalog(string $keyword, int $limit): CatalogSearchResponse
    {
        return $this->search($keyword, $limit, $this->books_query_config, false);
    }

    public function searchVideo(string $keyword, int $limit): CatalogSearchResponse
    {
        return $this->search($keyword, $limit, $this->video_query_config, false);
    }

    public function searchArticle(string $keyword, int $limit): CatalogSearchResponse
    {
        return $this->search($keyword, $limit, $this->article_query_config, true);
    }

    private function search(string $keyword, int $limit, QueryConfig $config, bool $is_pci): CatalogSearchResponse
    {
        // First check cache for search result.
        $cache_item = $this->cache->getItem($this->cacheKey($keyword, $limit));

        if ($cache_item->isHit()) {
            $result = $cache_item->get();
        } else {

            // Not in cache, request from Ex Libris.
            $result = $this->sendSearchQuery($keyword, $limit, $config);


            // Non-Primo Central results have extra fields
            if (! $is_pci) {

                // Load display types
                foreach ($result->getDocs() as $doc) {
                    $doc->setType($this->displayType($doc));
                }

                // Load video thumbs
                $this->video_thumbs->fetch($result);
            }

            // Add to cache.
            $cache_item->set($result);
            $cache_item->expiresAfter(self::CACHE_LIFETIME);
            $cache_item->tag(self::CACHED_SEARCH_RESULT_TAG);
            $this->cache->save($cache_item);
        }

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
        /**
         * @var $physical_docs Doc[]
         */
        $physical_docs = array_filter($results->getDocs(), function ($doc) {
            return $doc->isPhysical();
        });

        $holding_ids = $this->getHoldingIdsForRTA($physical_docs);
        $items = $this->alma->checkAvailability($holding_ids);
        $this->reconcileRealTimeAvailability($physical_docs, $items);
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
            $doc->setAvailable(false);
            foreach ($doc->getHoldings() as $holding) {
                if ($holding->getAvailabilityStatus() === 'available') {
                    $doc->setAvailable(true);
                }
                $holding_id = $holding->getIlsId();
                if (isset($items[$holding_id])) {
                    $this->populateHolding($holding, $items[$holding_id][0]);

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

    protected function cacheKey(string $keyword, string $limit): string
    {
        return "bcbento-catalog-search_$keyword-$limit";
    }

    /**
     * Send a search query to Ex Libris
     *
     * @param string $keyword
     * @param int $limit
     * @param QueryConfig $config
     * @return CatalogSearchResponse
     * @throws \BCLib\PrimoClient\Exceptions\BadAPIResponseException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function sendSearchQuery(string $keyword, int $limit, QueryConfig $config): CatalogSearchResponse
    {
        $query = new Query(Query::FIELD_ANY, Query::PRECISION_CONTAINS, $keyword);
        $request = new SearchRequest($config, $query);
        $request->limit($limit);

        $json = $this->client->get($request->url());
        return new CatalogSearchResponse(SearchTranslator::translate($json));
    }
}
