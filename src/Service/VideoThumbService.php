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

/**
 * Gets thumbnail images to use on video display
 *
 * We usually need to fetch thumbnails from many different videos from several different
 * providers, so the VideoThumbService runs in parallel. To fetch screencaps:
 *
 *     // Build the VideoThumbService
 *     $thumbs = new VideoThumbService($symfony_cache)
 *
 *     // Prepare all the video services we might need.
 *     $thumbs->addProvider($medicitv_provider);
 *     $thumbs->addProvider($metondemand_provider);
 *     $thumbs->addProvider($some_other_provider);
 *
 *     // Fetch screencaps for the videos in a PrimoClient\SearchResponse and
 *     // update the SearchResponse object with the thumbs.
 *     $thumbs->fetch($search_response);
 *
 * Use the ScreencapProvider interface to add new screencap providers.
 *
 * @package App\Service
 */
class VideoThumbService
{
    /**  @var AdapterInterface */
    private $cache;

    /** @var ScreencapProvider[] */
    private $providers = [];

    /** @var Promise\PromiseInterface[] */
    private $promises;

    // Expire cached thumbnails after one month (in seconds)
    private const CACHE_LIFETIME = 60 * 60 * 24 * 30;

    // Tag for tracking cached thumbnails
    private const CACHED_THUMBNAIL_TAG = 'video_thumb';

    public function __construct(AdapterInterface $cache)
    {
        $this->cache = new TagAwareAdapter($cache);
    }

    /**
     * Add a provider
     *
     * @param ScreencapProvider $provider
     */
    public function addProvider(ScreencapProvider $provider): void
    {
        $this->providers[] = $provider;
    }

    /**
     * Fetch screencaps for all videos in a SearchResponse
     *
     * Fetching uses Guzzle's promises (https://github.com/guzzle/promises) to facilitate
     * concurrent fetching. If you haven't worked with promises before (e.g. in Javascript),
     * read up on them before trying to understand this.
     *
     * @param SearchResponse $response
     * @throws InvalidArgumentException
     */
    public function fetch(SearchResponse $response): void
    {
        $this->promises = [];

        /** @var $cache_items CacheItem[] */
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
                'video'      => $doc
            ];
        }

        // Wait for outstanding requests to be settled and add the values to cache.
        $settled_promises = \GuzzleHttp\Promise\Utils::settle($this->promises)->wait();
        foreach ($settled_promises as $id => $promise) {
            if (isset($promise['value'])) {
                $cache_items[$id]['cache_item']->set($promise['value']);
            }

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

    /**
     * Build cache key string
     *
     * @param $id
     * @return string
     */
    private function cacheKey($id): string
    {
        return "bcbento_video-thumb_$id";
    }

    /**
     * Get a screencap for a Doc
     *
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

    /**
     * Locate a FoD screencap
     *
     * Films On Demand screencaps are built from the Films On Demand ID. Sometimes this ID is
     * stored in the PNX records as a search field, and sometimes we can extract it from another
     * link in a PNX link field.
     *
     * TODO Refactor Films On Demand cap fetching to a ScreencapProvider implementation
     * @param Doc $doc
     * @return String|null
     */
    private function getFilmsOnDemandCap(Doc $doc): ?string
    {
        $sources = $doc->pnx('display', 'lds30');

        if (!isset($sources[0]) || $sources[0] !== 'FILMS ON DEMAND') {
            return null;
        }

        // First try to get ID from custom PNX field. The field might have multiple IDs. The
        // correct ID is usually the one without the 's' in it.
        $pnx13 = $doc->pnx('search', 'lsr13');
        foreach ($pnx13 as $id) {
            if (!str_contains($id, 's')) {
                return $this->filmsOnDemandUrl(array_pop($pnx13));
            }
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

    /**
     * Build FilmsOnDemand URL
     *
     * @param string $fod_id
     * @return string
     */
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