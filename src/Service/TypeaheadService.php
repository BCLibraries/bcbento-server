<?php

namespace App\Service;

use App\Entity\TypeaheadEntry;
use App\Entity\TypeaheadResponse;
use Elastic\Elasticsearch\Client;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\CacheItem;

class TypeaheadService implements LoggerAwareInterface
{
    /**
     * @var Client
     */
    private $elasticsearch;

    /**
     * @var TagAwareAdapter
     */
    private $cache;

    // Expire cached typeaheads after one week
    private const CACHE_LIFETIME = 60 * 60 * 24 * 7;

    // Tag cached typeaheads for deletion.
    private const CACHE_TAG = 'typeahead-hint';

    /**
     * @var LoggerInterface
     */
    public $logger;

    public function __construct(Client $elasticsearch, AdapterInterface $cache)
    {
        $this->elasticsearch = $elasticsearch;
        $this->cache = new TagAwareAdapter($cache);
    }

    /**
     * Retrieve a set of search suggestions
     *
     * Suggestions may come from Elasticsearch or cache.
     *
     * @param string $input
     * @return TypeaheadResponse
     */
    public function fetch(string $input): TypeaheadResponse
    {
        try {
            $cache_item = $this->cache->getItem($this->cacheKey($input));
        } catch (CacheException $e) {
            // Cache failed? Return directly from Elasticsearch.
            $this->logger->error("Typeahead cache error: {$e->getMessage()}\n{$e->getTraceAsString()}");
            return $this->fetchSuggestions($input);
        }

        if ($cache_item->isHit()) {
            return $cache_item->get();
        }

        $response = $this->fetchSuggestions($input);
        $this->cacheResponse($cache_item, $response);
        return $this->fetchSuggestions($input);
    }

    /**
     * Fetch suggestions from Elasticsearch
     *
     * @param string $input
     * @return TypeaheadResponse
     */
    private function fetchSuggestions(string $input): TypeaheadResponse
    {
        $params = [
            'index' => 'autocomplete',
            'body' => [
                'ac' => [
                    'text' => $input,
                    'completion' => [
                        'field' => 'suggest'
                    ]
                ]
            ]
        ];

        $suggestions = $this->elasticsearch->suggest($params);

        $results = new TypeaheadResponse();

        if (!isset($suggestions['ac'][0])) {
            return $results;
        }

        foreach ($suggestions['ac'][0]['options'] as $term) {
            $value = rtrim($term['text'], ' ,:/\\.');
            $results->addEntry(new TypeaheadEntry($value, ''));
        }
        return $results;
    }

    private function cacheKey($input): string
    {
        return "bcbento_typeahead_$input";
    }

    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param CacheItem $cache_item
     * @param TypeaheadResponse $response
     */
    protected function cacheResponse(CacheItem $cache_item, TypeaheadResponse $response): void
    {
        try {
            // Cache item, setting cache lifetime and tagging for easy removal.
            $cache_item->set($response);
            $cache_item->expiresAfter(self::CACHE_LIFETIME);
            $cache_item->tag(self::CACHE_TAG);
            $this->cache->save($cache_item);
        } catch (InvalidArgumentException $e) {
            // Cache failed? Don't worry about it, but write to log.
            $this->logger->error("Typeahead cache error: {$e->getMessage()}\n{$e->getTraceAsString()}");
        } catch (CacheException $e) {
            $this->logger->error("Typeahead cache error: {$e->getMessage()}\n{$e->getTraceAsString()}");
        }
    }
}
