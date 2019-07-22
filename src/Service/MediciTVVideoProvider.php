<?php

namespace App\Service;

use BCLib\PrimoClient\Doc;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

class MediciTVVideoProvider implements VideoProvider
{
    use ParsesForScreenCapsTrait;

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
     * Returns true if a screencap could be grabbed from this service.
     *
     * @param Doc $doc
     * @return bool
     */
    public function test(Doc $doc): bool
    {
        $links = $doc->getLinkToResource();
        if (!isset($links[0])) {
            return false;
        }
        $link = $links[0]->getUrl();
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
        $link = $doc->getLinkToResource()[0]->getUrl();
        return $this->guzzle->getAsync($link, ['allow_redirects' => true])->then(
            function (ResponseInterface $response) use ($doc) {
                return $this->getOpenGraphImage((string)$response->getBody());
            }
        );
    }
}