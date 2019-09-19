<?php

namespace App\Service;

use BCLib\PrimoClient\Doc;
use BCLib\PrimoClient\Link;
use BCLib\PrimoClient\SearchResponse;
use GuzzleHttp\Promise;
use Psr\Cache\InvalidArgumentException;
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
     * @param SearchResponse $response
     * @throws InvalidArgumentException
     */
    public function fetch(SearchResponse $response): void
    {
        $this->promises = [];

        /**
         * @var $cache_items CacheItem[]
         */
        $cache_items = [];

        foreach ($response->getDocs() as $doc) {
            $cache_item = $this->cache->getItem($this->cacheKey($doc->id));

            // Set Films On Demand URLs separately, since they don't require HTTP
            if ($screencap = $this->getDesignatedCover($doc)) {
                $doc->setScreenCap($screencap);
                $cache_item->set($screencap);
            } elseif ($screencap = $this->getFilmsOnDemandCap($doc)) {
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
     * @param Doc $doc
     */
    protected function extractScreenCap(Doc $doc): void
    {
        foreach ($this->providers as $provider) {
            if ($provider->test($doc)) {
                $this->promises[$doc->id] = $provider->getScreenCap($doc);
            }
        }
    }

    private function getFilmsOnDemandCap(Doc $doc): ?String
    {
        $sources = $doc->pnx('display', 'lds30');

        if (!isset($sources[0]) || $sources[0] !== 'FILMS ON DEMAND') {
            return null;
        }

        // First try to get ID from custom PNX field.
        $pnx13 = $doc->pnx('search', 'lsr13');
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

    private function filmsOnDemandUrl(string $fod_id): string
    {
        return "https://fod.infobase.com/image/$fod_id";
    }

    /**
     * Get a designated cover image
     *
     * Some services (e.g. Kanopy) include a screen cap with the MARC record.
     */
    private function getDesignatedCover(Doc $doc): ?string
    {
        $links = $doc->getLinks();

        if (!isset($links['addlink'])) {
            return null;
        }

        /**
         * @var $link Link
         */
        foreach ($links['addlink'] as $link) {
            if ($link->getLabel() === 'Cover Image') {
                return $link->getUrl();
            }
        }

        return null;
    }
}