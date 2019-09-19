<?php

namespace App\Service;

use DOMDocument;

trait ParsesForScreenCapsTrait
{
    /**
     * @param string $html
     * @return DOMDocument
     */
    private function loadDOM(string $html): DOMDocument
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_use_internal_errors(false);
        return $dom;
    }

    private function getOpenGraphImage(string $html): ?string
    {
        $dom = $this->loadDOM($html);
        $xpath = new \DOMXPath($dom);
        $metas = $xpath->query("//meta[@property='og:image']");
        for ($i = 0; $i < $metas->length; $i++) {
            $url = $metas->item($i)->getAttribute('content');
            if ($url) {
                return $url;
            }
        }
        return null;
    }
}