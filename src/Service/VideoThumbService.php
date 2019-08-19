<?php

namespace App\Service;

use App\Entity\Video;
use App\Entity\VideoSearchResponse;
use GuzzleHttp\Promise;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
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

    // Expire cached thumbnails after one month
    private const CACHE_LIFETIME = 60 * 60 * 24 * 30;

    // Tag for tracking cached thumbnails
    private const CACHED_THUMBNAIL_TAG = 'video_thumb';

    public function __construct(AdapterInterface $cache)
    {
        $this->cache = new TagAwareAdapter($cache);
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
                $screencap = $this->getFilmsOnDemandCap($doc);
                $doc->setScreenCap($screencap);
                $cache_item->set($screencap);
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

            // All images should expire after one month. Also tag thumbnail caches
            // in case we need to expire them earlier.
            $cache_items[$id]['cache_item']->expiresAfter(self::CACHE_LIFETIME);
            $cache_items[$id]['cache_item']->tag(self::CACHED_THUMBNAIL_TAG);

            $this->cache->save($cache_items[$id]['cache_item']);
        }

        // Set the screen cap value on the video.
        foreach ($cache_items as $id => $item) {
            $screencap = $item['cache_item']->get();
            if ($screencap) {
                $cache_items[$id]['video']->setScreenCap($item['cache_item']->get());
            }
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

    private function getFilmsOnDemandCap(Video $doc): ?String
    {
        // First try to get ID from custom PNX field.
        $pnx13 = $doc->pnx('search','lsr13');
        if ($pnx13 && $pnx13[0]) {
            return $this->filmsOnDemandUrl($pnx13[0]);
        }

        // Next try to find it in links.
        $links = $doc->getLinkToResource();
        if (!isset($links[0])) {
            return null;
        }

        $pattern = '/xtid=(\d*)/';
        preg_match($pattern, $links[0]->getUrl(), $matches);

        if (isset($matches[1])) {
            return $this->filmsOnDemandUrl($matches[1]);
        }

        return null;
    }

    private function filmsOnDemandUrl(string $fod_id) {
        return "https://fod.infobase.com/image/$fod_id";
    }
}