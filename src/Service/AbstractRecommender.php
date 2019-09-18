<?php

namespace App\Service;

use Elasticsearch\Client;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

abstract class AbstractRecommender
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
    public $cache;

    protected $index;

    // Expire cached recommendations after one month
    private const CACHE_LIFETIME = 60 * 60 * 24 * 30;

    // Tag for tracking cached recommendations
    private const CACHE_TAG = 'recommendation';

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
        $cache_item = $this->cache->getItem($this->recommenderLookupCacheKey($keyword));

        if ($cache_item->isHit()) {
            return $cache_item->get();
        }

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
            if (\count($facet['terms'])) {
                $facet_array[] = $facet['terms'];
            }
        }

        $cache_item->set($facet_array);
        $cache_item->tag(self::CACHE_TAG);
        $cache_item->expiresAfter(self::CACHE_LIFETIME);
        $this->cache->save($cache_item);

        return $facet_array;
    }

    protected function recommenderLookupCacheKey(string $term): string
    {
        return 'bcbento_recommender-search_' . sha1($term);
    }
}