<?php

namespace App\Service;

use BCLib\PrimoClient\Doc;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Grabs screen caps for MediciTV videos
 *
 * MediciTV stores information about the video in Open Graph (https://ogp.me/) metadata
 * embedded in the video page. Links to the MediciTV pages are in the lln03 field of
 * the PNX record.
 *
 * @package App\Service
 */
class MediciTVScreencapProvider implements ScreencapProvider
{
    use LoadsOpenGraphImages;

    /**
     * @var Client
     */
    private $guzzle;

    public function __construct(Client $guzzle)
    {
        $this->guzzle = $guzzle;
    }

    /**
     * Is a doc from this service?
     *
     * Returns true if a screencap can be grabbed for a Doc using the Medici TV rules.
     *
     * @param Doc $doc
     * @return bool
     */
    public function test(Doc $doc): bool
    {
        // Look in the lln03 field of the Primo record
        $links = $doc->getLinks();

        if (! isset($links['lln03'])) {
            return false;
        }

        if (!isset($links['lln03'][0])) {
            return false;
        }

        $link = $links['lln03'][0]->getUrl();
        return (preg_match('#(https?://edu.medici.tv/movies.*)\$\$D#', $link) !== false);
    }

    /**
     * Get the screencap
     *
     * Returns a promise that when fulfilled returns a screencap or null.
     *
     * @param Doc $doc
     * @return PromiseInterface
     */
    public function getScreenCap(Doc $doc): PromiseInterface
    {
        // Fetch the Medici TV page and look for OpenGraph image metadata containing
        // a screencap URL.
        $link = $doc->getLinks()['lln03'][0]->getUrl();
        return $this->guzzle->getAsync($link, ['allow_redirects' => true])->then(
            function (ResponseInterface $response) use ($doc) {
                return $this->getOpenGraphImage((string)$response->getBody());
            }
        );
    }
}