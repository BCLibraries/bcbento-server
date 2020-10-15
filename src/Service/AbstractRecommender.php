<?php

namespace App\Service;

use function count;
use Elasticsearch\Client;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\CacheItem;

/**
 * Recommender for a resource
 *
 * Implementations fo AbstractRecommender lookup users in an Elasticsearch. The recommendation
 * process has two steps:
 *
 *     1. Run the search term against an Elasticsearch index containing all Primo records
 *        tagged with LibGuides taxonomy terms (https://bcwiki.bc.edu/display/UL/LibGuides+Taxonomy)
 *        Extract the most common terms from records most common terms found in that result
 *        (through Elasticsearch term facets/aggregations)
 *     2. Run a search against the index containing the resources to recommend using the most
 *        common terms
 *
 * For example to recommend librarians based on a search for "Mark Twain":
 *
 *     1. Search the all-Primo index for "Mark Twain", returning a result that shows
 *        the most common taxonomy terms in "Mark Twain" records to be 'English Language and
 *        Literature' and 'American Culture'.
 *     2. Search the librarians index for librarians tagged with 'English Language and Literature'
 *        or 'American Culture'

 *
 * @package App\Service
 */
abstract class AbstractRecommender implements LoggerAwareInterface
{
    /** @var Client */
    private $elasticsearch;

    // TODO: move Elasticsearch version into the ES client
    /** @var string */
    private $elasticsearch_version;

    /** @var TagAwareAdapter */
    protected $cache;

    /**
     * The name of the Elasticsearch index containing the items to be
     * recommended.
     *
     * @var string
     */
    protected $index;

    // Expire cached recommendations after one month (in seconds)
    private const CACHE_LIFETIME = 60 * 60 * 24 * 30;

    // Tag for tracking cached recommendations
    private const CACHE_TAG = 'recommendation';

    /** @var LoggerInterface */
    protected $logger;

    /**
     * AbstractRecommender constructor.
     *
     * @param Client $elasticsearch
     * @param AdapterInterface $cache
     * @param string $elasticsearch_version
     */
    public function __construct(Client $elasticsearch, AdapterInterface $cache, $elasticsearch_version = '1.2.1')
    {
        $this->cache = new TagAwareAdapter($cache);
        $this->elasticsearch = $elasticsearch;
        $this->elasticsearch_version = $elasticsearch_version;
    }

    /**
     * Fetch a recommendation
     *
     * @param string $keyword
     * @return mixed
     */
    public function fetchRecommendation(string $keyword)
    {
        // Query the all-Primo Elasticsearch database for relevant terms.
        $terms_response = $this->getRelevantTerms($keyword);

        // Query the resources index using those terms.
        return $this->getResult($keyword, $terms_response);
    }

    /**
     * Search resources to recommend
     *
     * @param string $keyword
     * @param array $terms_response terms from all-Primo database
     * @return mixed
     */
    public function getResult(string $keyword, array $terms_response)
    {
        $params = [
            'index' => $this->index,
            'body' => $this->buildQuery($keyword, $terms_response)
        ];
        $resources = $this->elasticsearch->search($params);
        return $this->buildResponse($resources);
    }

    /**
     * Build a query to fetch from the index
     *
     * @param string $keyword
     * @param array $terms_response
     * @return mixed
     */
    abstract public function buildQuery(string $keyword, array $terms_response);

    /**
     * Build an appropriate response object
     *
     * @param array $terms_response
     * @return mixed
     */
    abstract public function buildResponse(array $terms_response);

    /**
     * Get terms to search the resource index
     *
     * @param string $keyword
     * @return array
     */
    protected function getRelevantTerms(string $keyword): array
    {
        try {
            $cache_item = $this->cache->getItem($this->recommenderLookupCacheKey($keyword));
        } catch (InvalidArgumentException $e) {

            // Cache failed? Log the error, bypass cache and search Elasticsearch directly.
            $this->logger->error("Recommender cache error: {$e->getMessage()} {$e->getTraceAsString()}");
            return $this->queryElasticsearchForTerms($keyword);
        }

        if ($cache_item->isHit()) {
            return $cache_item->get();
        }

        // If cache didn't hit, search Elasticsearch.
        $facet_array = $this->queryElasticsearchForTerms($keyword);
        $this->cacheResponse($cache_item, $facet_array);
        return $facet_array;
    }

    /**
     * Cache key
     *
     * @param string $term search terms
     * @return string
     */
    protected function recommenderLookupCacheKey(string $term): string
    {
        return 'bcbento_recommender-search_' . sha1($term);
    }

    /**
     * Send query to the all-Primo ES index to get recommender terms
     *
     * @param string $keyword
     * @return array
     */
    protected function queryElasticsearchForTerms(string $keyword): array
    {
        // TODO Is there more we need to do to sanitize Elasticsearch queries?
        $keyword = ElasticSearchCleaner::clean($keyword);

        // Elasticsearch changed term scoring syntax with version 1.3.2.
        $score_script = ($this->elasticsearch_version < '1.3.2') ? 'doc.score' : '_score';

        // Build request body.
        $params = [];
        $params['index'] = 'catalog';
        $params['body'] = [
            'query' => [
                'query_string' => [
                    'query' => $keyword,
                    'default_operator' => 'AND',

                    // Weights are just a guess...
                    'fields' => [
                        'title^10',
                        'author^5',
                        'subjects^3',
                        'description',
                        'toc',
                        'issn',
                        'isbn'
                    ],
                    'use_dis_max' => false
                ]
            ],
            'from' => 0,
            'size' => 0, // We don't actually need to fetch any results, just the term facets.
            'sort' => [],

            // The most common terms come from ES facets ("aggregations" in later ES versions).
            // LCCDep1 terms are broadest, LCCDep3 terms are narrowest.
            'facets' => [
                'LCCDep1' => [
                    'terms_stats' => [
                        'key_field' => 'tax1',
                        'value_script' => $score_script
                    ]
                ],
                'LCCDep2' => [
                    'terms_stats' => [
                        'key_field' => 'tax2',
                        'value_script' => $score_script
                    ]
                ],
                'LCCDep3' => [
                    'terms_stats' => [
                        'key_field' => 'tax3',
                        'value_script' => $score_script
                    ]
                ]
            ]
        ];


        $response = $this->elasticsearch->search($params);

        // Return any found terms.
        $facet_array = [];
        foreach ($response['facets'] as $facet) {
            if (count($facet['terms'])) {
                $facet_array[] = $facet['terms'];
            }
        }
        return $facet_array;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Save a recommender response to the cache
     *
     * @param CacheItem $cache_item
     * @param array $facet_array
     */
    protected function cacheResponse(CacheItem $cache_item, array $facet_array): void
    {
        try {

            $cache_item->set($facet_array);
            $cache_item->tag(self::CACHE_TAG);
            $cache_item->expiresAfter(self::CACHE_LIFETIME);
            $this->cache->save($cache_item);

        } catch (InvalidArgumentException $e) {
            // Cache failed? Don't worry about it, but write to log.
            $this->logger->error("Recommender cache error: {$e->getMessage()} {$e->getTraceAsString()}");
        } catch (CacheException $e) {
            // Cache failed? Don't worry about it, but write to log.
            $this->logger->error("Recommender cache error: {$e->getMessage()} {$e->getTraceAsString()}");
        }
    }


}