<?php

namespace App\Service;

use App\Entity\Webpage;
use App\Entity\WebsiteSearchResponse;
use Elasticsearch\Client;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\CacheItem;

/**
 * Search the BC Libraries Websites
 *
 * Our LibGuides are indexed in Elasticsearch nightly by a crawler on libdev at:
 *
 *     /apps/bcbento.versions/current/server/util/crawl_libguides.php
 *
 * TODO Move LibGuides indexer into this repository
 *
 */
class WebsiteSearch implements LoggerAwareInterface
{
    /** @var Client */
    private $elasticsearch;

    /** @var TagAwareAdapter */
    public $cache;

    // Expire cached searches after one day (in seconds)
    private const CACHE_LIFETIME = 60 * 60 * 24;

    // Tag for tracking cached searches
    private const CACHE_TAG = 'website_search';

    /** @var LoggerInterface */
    private $logger;

    public function __construct(Client $elasticsearch, AdapterInterface $cache)
    {
        $this->cache = new TagAwareAdapter($cache);
        $this->elasticsearch = $elasticsearch;
    }

    /**
     * Search the website
     *
     * @param string $keyword
     * @return WebsiteSearchResponse
     */
    public function fetch(string $keyword): WebsiteSearchResponse
    {

        // First look for the search in cache.
        try {
            $cache_item = $this->cache->getItem($this->cacheKey($keyword));
        } catch (InvalidArgumentException $e) {

            // If cache lookup fails, return bare Elasticsearch response.
            $this->logger->error("Site search cache error: {$e->getMessage()}\n{$e->getTraceAsString()}");
            return $this->queryElasticsearch($keyword);
        }

        if ($cache_item->isHit()) {
            return $cache_item->get();
        }

        // If no result, look in Elasticsearch and cache the result.
        $response = $this->queryElasticsearch($keyword);
        $this->cacheResponse($cache_item, $response);
        return $response;
    }

    /**
     * Build the user's response
     *
     * @param $json_response
     * @param $keyword
     * @return WebsiteSearchResponse
     */
    private function buildResponse($json_response, $keyword): WebsiteSearchResponse
    {
        $response = new WebsiteSearchResponse($json_response['hits']['total'], $keyword);
        foreach ($json_response['hits']['hits'] as $page) {
            $response->addDoc($this->buildItem($page));
        }

        return $response;
    }

    /**
     * Build a single item
     *
     * @param $page_json
     * @return Webpage
     */
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

    /**
     * Cache key string
     *
     * @param $keyword
     * @return string
     */
    private function cacheKey($keyword): string
    {
        return "bcbento_site_search_$keyword";
    }

    /**
     * Send search query to Elasticsearch
     *
     * @param string $keyword
     * @return WebsiteSearchResponse
     */
    private function queryElasticsearch(string $keyword): WebsiteSearchResponse
    {
        // The query
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

    /**
     * Cache search response
     *
     * @param CacheItem $cache_item
     * @param WebsiteSearchResponse $response
     */
    private function cacheResponse(CacheItem $cache_item, WebsiteSearchResponse $response): void
    {
        $cache_item->set($response);
        $cache_item->expiresAfter(self::CACHE_LIFETIME);
        try {
            $cache_item->tag(self::CACHE_TAG);
        } catch (InvalidArgumentException $e) {
            $this->logger->error("Site search cache error: {$e->getMessage()}\n{$e->getTraceAsString()}");
        } catch (CacheException $e) {
            $this->logger->error("Site search cache error: {$e->getMessage()}\n{$e->getTraceAsString()}");
        }
        $this->cache->save($cache_item);
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
}