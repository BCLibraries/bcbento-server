<?php

namespace App\Service;

use App\Entity\Webpage;
use App\Entity\WebsiteSearchResponse;
use Elasticsearch\Client;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\CacheItem;

/**
 * Search the BC Website
 */
class WebsiteSearch
{
    /**
     * @var Client
     */
    private $elasticsearch;

    /**
     * @var TagAwareAdapter
     */
    public $cache;

    // Expire cached searches after one day
    private const CACHE_LIFETIME = 60 * 60 * 24;

    // Tag for tracking cached searches
    private const CACHE_TAG = 'website_search';

    public function __construct(Client $elasticsearch, AdapterInterface $cache)
    {
        $this->cache = new TagAwareAdapter($cache);
        $this->elasticsearch = $elasticsearch;
    }

    public function fetch(string $keyword): WebsiteSearchResponse
    {
        try {
            $cache_item = $this->cache->getItem($this->cacheKey($keyword));
        } catch (InvalidArgumentException $e) {

            // If cache lookup fails, return bare Elasticsearch response.
            return $this->queryElasticsearch($keyword);
        }

        if ($cache_item->isHit()) {
            return $cache_item->get();
        }

        $response = $this->queryElasticsearch($keyword);
        $this->cacheResponse($cache_item, $response);
        return $response;
    }

    private function buildResponse($json_response, $keyword): WebsiteSearchResponse
    {
        $response = new WebsiteSearchResponse($json_response['hits']['total'], $keyword);
        foreach ($json_response['hits']['hits'] as $page) {
            $response->addDoc($this->buildItem($page));
        }

        return $response;
    }

    private function buildItem($page_json): Webpage
    {
        return new Webpage(
            $page_json['_source']['url'],
            $page_json['_source']['title'],
            $page_json['_source']['guide_title'],
            $page_json['_source']['guide_url'],
            isset($page_json['highlight']) ? $page_json['highlight']['text'] : [],
            $page_json['_source']['updated']
        );
    }

    private function cacheKey($keyword): string
    {
        return "bcbento_site_search_$keyword";
    }

    private function queryElasticsearch(string $keyword): WebsiteSearchResponse
    {
        $params = [
            'index' => 'website',
            'body' => [
                'query' => [
                    'multi_match' => [
                        'query' => $keyword,
                        'fields' => [
                            'title^5',
                            'title.english^5',
                            'guide_title^6',
                            'guide_title.english^6',
                            'guide_subjects^4',
                            'guide_subjects.english^4',
                            'guide_tags^4',
                            'guide_tags.english^4',
                            'url^5',
                            'guide_url^6',
                            'guide_description^6',
                            'guide_description.english^6',
                            'text^1'
                        ],
                        'operator' => 'and'
                    ]
                ],
                'from' => 0,
                'size' => 3,
                'highlight' => [
                    'fields' =>
                        [
                            'text' => (object)[
                                'fragment_size' => 150
                            ]
                        ]
                ]
            ]
        ];
        $elasticsearch_response = $this->elasticsearch->search($params);
        return $this->buildResponse($elasticsearch_response, $keyword);
    }

    private function cacheResponse(CacheItem $cache_item, WebsiteSearchResponse $response): void
    {
        $cache_item->set($response);
        $cache_item->expiresAfter(self::CACHE_LIFETIME);
        try {
            $cache_item->tag(self::CACHE_TAG);
        } catch (InvalidArgumentException $e) {
            // Don't fail on bad cache tagging
        } catch (CacheException $e) {
            // Don't fail on bad cache tagging
        }
        $this->cache->save($cache_item);
    }
}