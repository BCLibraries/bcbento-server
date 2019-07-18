<?php

namespace App\Service;

use BCLib\PrimoClient\Doc;
use GuzzleHttp\Promise;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\CacheItem;

class VideoThumbService
{
    /**
     * @var Doc[]
     */
    private $queue = [];

    /**
     * @var AdapterInterface
     */
    private $cache;

    /**
     * @var VideoProvider[]
     */
    private $providers = [];

    /**
     * @var Promise\PromiseInterface[]
     */
    private $promises;

    public function __construct(AdapterInterface $cache = null)
    {
        $this->cache = $cache;
    }

    public function addProvider(VideoProvider $provider): void
    {
        $this->providers[] = $provider;
    }

    /**
     * @param Doc[] $docs
     * @return array
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function fetch(array $docs): array
    {
        $this->promises = [];

        /**
         * @var $cache_items CacheItem[]
         */
        $cache_items = [];

        $results = [];

        foreach ($docs as $doc) {
            $cache_item = $this->cache->getItem($this->cacheKey($doc->id));
            if (!$cache_item->isHit()) {
                $this->extractScreeCap($doc);
            }
            $cache_items[$doc->id] = $cache_item;
        }

        $settled_promises = Promise\settle($this->promises)->wait();

        foreach ($settled_promises as $id => $promise) {
            $cache_items[$id]->set($promise['value']);
            $this->cache->save($cache_items[$id]);
        }

        foreach ($cache_items as $id => $item) {
            $results[$id] = $item->get();
        }

        return $results;
    }

    private function cacheKey($id): string
    {
        return "bcbento_video-thumb_$id";
    }

    /**
     * @param Doc $doc
     */
    protected function extractScreeCap(Doc $doc): void
    {
        foreach ($this->providers as $provider) {
            if ($provider->test($doc)) {
                $this->promises[$doc->id] = $provider->getScreenCap($doc);
            }
        }
    }
}