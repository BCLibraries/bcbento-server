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

abstract class AbstractRecommender implements LoggerAwareInterface
{
    /**
     * @var Client
     */
    private $elasticsearch;

    /**
     * @var string
     */
    private $elasticsearch_version;

    /**
     * @var TagAwareAdapter
     */
    protected $cache;

    protected $index;

    // Expire cached recommendations after one month
    private const CACHE_LIFETIME = 60 * 60 * 24 * 30;

    // Tag for tracking cached recommendations
    private const CACHE_TAG = 'recommendation';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(Client $elasticsearch, AdapterInterface $cache, $elasticsearch_version = '1.2.1')
    {
        $this->cache = new TagAwareAdapter($cache);

        $this->elasticsearch = $elasticsearch;
        $this->elasticsearch_version = $elasticsearch_version;
    }

    public function fetch(string $keyword)
    {
        $terms_response = $this->getRelevantTerms($keyword);
        return $this->getResult($keyword, $terms_response);
    }

    public function getResult(string $keyword, array $terms_response)
    {
        $params = [
            'index' => $this->index,
            'body' => $this->buildQuery($keyword, $terms_response)
        ];
        $librarians = $this->elasticsearch->search($params);
        return $this->buildResponse($librarians);
    }

    abstract public function buildQuery(string $keyword, array $terms_response);

    abstract public function buildResponse(array $terms_response);

    protected function getRelevantTerms(string $keyword): array
    {
        try {
            $cache_item = $this->cache->getItem($this->recommenderLookupCacheKey($keyword));
        } catch (InvalidArgumentException $e) {

            // Cache failed? Return directly from Elasticsearch.
            $this->logger->error("Recommender cache error: {$e->getMessage()} {$e->getTraceAsString()}");
            return $this->queryElasticsearchForTerms($keyword);
        }

        if ($cache_item->isHit()) {
            return $cache_item->get();
        }

        $facet_array = $this->queryElasticsearchForTerms($keyword);
        $this->cacheResponse($cache_item, $facet_array);
        return $facet_array;
    }

    protected function recommenderLookupCacheKey(string $term): string
    {
        return 'bcbento_recommender-search_' . sha1($term);
    }

    /**
     * @param string $keyword
     * @return array
     */
    protected function queryElasticsearchForTerms(string $keyword): array
    {
        $keyword = str_replace(':', '', $keyword);

        $score_script = ($this->elasticsearch_version < '1.3.2') ? 'doc.score' : '_score';

        $params = [];
        $params['index'] = 'catalog';
        $params['body'] = [
            'query' => [
                'query_string' => [
                    'query' => $keyword,
                    'default_operator' => 'AND',
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
            'size' => 10,
            'sort' => [],
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

        $facet_array = [];

        $response = $this->elasticsearch->search($params);

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