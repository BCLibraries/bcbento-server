<?php

namespace App\Service;

use BCLib\PrimoClient\Doc;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;

class FilmsOnDemandProvider implements VideoProvider
{
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
        $source = $doc->pnx('display', 'lds30');
        return $source === 'FILMS ON DEMAND';
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
        $promise = new Promise();
        $promise = $promise->then(function (Doc $doc) {
            $doc->cover_images[0] = $this->getCapURL($doc);
        });
        $promise->resolve($doc);
        return $promise;
    }

    private function getCapURL(Doc $doc): ?String
    {
        $links = $doc->getLinkToResource();

        if (!isset($links[0])) {
            return null;
        }

        $pattern = '/xtid=(\d*)\$\$/';
        preg_match($pattern, $links[0], $matches);

        if (isset($matches[1])) {
            return "https://fod.infobase.com/image/{$matches[1]}";
        }

        return null;
    }
}