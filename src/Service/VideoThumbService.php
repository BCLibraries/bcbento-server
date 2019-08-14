<?php

namespace App\Service;

use App\Entity\Video;
use App\Entity\VideoSearchResponse;
use GuzzleHttp\Promise;
use Psr\Log\LogLevel;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\CacheItem;

class VideoThumbService
{
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
     * @param VideoSearchResponse $response
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function fetch(VideoSearchResponse $response)
    {
        $this->promises = [];

        /**
         * @var $cache_items CacheItem[]
         */
        $cache_items = [];

        foreach ($response->getDocs() as $doc) {
            $cache_item = $this->cache->getItem($this->cacheKey($doc->id));

            // Set Films On Demand URLs separately, since they don't require HTTP
            $sources = $doc->pnx('display', 'lds30');
            if (isset($sources[0]) && $sources[0] === 'FILMS ON DEMAND') {
                $cache_item->set($this->getFilmsOnDemandCapURL($doc));
            }

            // If the screen cap is not found in cache, try to request it.
            if (!$cache_item->isHit()) {
                $this->extractScreenCap($doc);
            }

            $cache_items[$doc->id] = [
                'cache_item' => $cache_item,
                'video' => $doc
            ];
        }

        // Wait for outstanding requests to be settled and add the values to cache.
        $settled_promises = Promise\settle($this->promises)->wait();
        foreach ($settled_promises as $id => $promise) {
            $cache_items[$id]['cache_item']->set($promise['value']);
            $this->cache->save($cache_items[$id]['cache_item']);
        }

        // Set the screen cap value on the video.
        foreach ($cache_items as $id => $item) {
            $cache_items[$id]['video']->setScreenCap($item['cache_item']->get());
        }
    }

    private function cacheKey($id): string
    {
        return "bcbento_video-thumb_$id";
    }

    /**
     * @param Video $doc
     */
    protected function extractScreenCap(Video $doc): void
    {
        foreach ($this->providers as $provider) {
            if ($provider->test($doc)) {
                $this->promises[$doc->id] = $provider->getScreenCap($doc);
            }
        }
    }

    private function getFilmsOnDemandCapURL(Video $doc): ?String
    {
        $links = $doc->getLinkToResource();

        if (!isset($links[0])) {
            return null;
        }

        $pattern = '/xtid=(\d*)/';
        preg_match($pattern, $links[0]->getUrl(), $matches);

        if (isset($matches[1])) {
            return "https://fod.infobase.com/image/{$matches[1]}";
        }

        return null;
    }
}