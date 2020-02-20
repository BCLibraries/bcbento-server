<?php

namespace App\Service;

use DOMDocument;
use DOMXPath;

/**
 * Load video from Open Graph protocol-compatible pages
 *
 * The Open Graph (https://ogp.me/) protocol embeds metadata in <meta> tags in resource
 * HTML documents. Open Graph images are stored in tags like:
 *
 *  <meta property="og:image" content="https://medicitv-c.imgix.net/movie/verdi-aida.jpg?auto=format&amp;q=85">
 *
 * This trait loads HTML documents and extracts Open Graph images.
 *
 * @package App\Service
 */
trait LoadsOpenGraphImages
{
    /**
     * Load a page DOM for parsing
     *
     * @param string $html
     * @return DOMDocument
     */
    private function loadDOM(string $html): DOMDocument
    {
        $dom = new DOMDocument();

        // Load the HTML, but don't die if there is an error. It isn't
        // that important.
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_use_internal_errors(false);
        return $dom;
    }

    /**
     * Find <meta property="og:image"/> elements and extract their value
     *
     * @param string $html
     * @return string|null
     */
    private function getOpenGraphImage(string $html): ?string
    {
        $dom = $this->loadDOM($html);
        $xpath = new DOMXPath($dom);
        $metas = $xpath->query("//meta[@property='og:image']");
        for ($i = 0; $i < $metas->length; $i++) {
            if ($metas->item($i)) {
                $url = $metas->item($i)->getAttribute('content');
                if ($url) {
                    return $url;
                }
            }
        }
        return null;
    }
}