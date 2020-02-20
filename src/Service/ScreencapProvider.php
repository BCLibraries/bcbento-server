<?php

namespace App\Service;

use BCLib\PrimoClient\Doc;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * Get video captures from video documents
 *
 * @package App\Service
 */
interface ScreencapProvider
{
    /**
     * Is a doc from this service?
     *
     * Returns true if a screencap could be grabbed from this service.
     *
     * @param Doc $doc
     * @return bool
     */
    public function test(Doc $doc): bool;

    /**
     * Get the screencap
     *
     * Returns a promise that when fulfilled returns a screencap or null.
     *
     * @param Doc $doc
     * @return PromiseInterface
     */
    public function getScreenCap(Doc $doc): PromiseInterface;
}