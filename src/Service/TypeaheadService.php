<?php

namespace App\Service;

use App\Entity\TypeaheadEntry;
use App\Entity\TypeaheadResponse;
use Elasticsearch\Client;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

class TypeaheadService
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
     * @throws \Psr\Cache\CacheException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function fetch(string $input): TypeaheadResponse
    {
        $cache_item = $this->cache->getItem($this->cacheKey($input));
        if ($cache_item->isHit()) {
            return $cache_item->get();
        }

        $response = $this->fetchSuggestions($input);

        // Cache item, setting cache lifetime and tagging for easy removal.
        $cache_item->set($response);
        $cache_item->expiresAfter(self::CACHE_LIFETIME);
        $cache_item->tag(self::CACHE_TAG);
        $this->cache->save($cache_item);

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
}
