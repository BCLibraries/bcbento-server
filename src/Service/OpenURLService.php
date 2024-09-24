<?php

namespace App\Service;

use SimpleXMLElement;

/**
 * Lookup values in OpenURL
 */
class OpenURLService
{
    /**
     * Try to get a direct link to an item
     *
     * Attempt to get a direct link to an item. If we can't, link the user to the
     * record in Primo.
     *
     * @param string $mms the item to lookup
     * @return string the best URL to refer the user to
     */
    public function lookup(string $mms): string
    {
        // Fallback URL will end up pointing to Primo.
        // @todo point directly at Primo
        $fallback_url = "https://bclib.bc.edu/libsearch/bc/keyword/$mms";

        // Can we find a direct link? If so, return that.
        return $this->getTargetLink($mms) ?? $fallback_url;
    }

    /**
     * @throws \Exception
     */
    public function getTargetLink(string $mms): ?string
    {
        // Send an OpenURL query, parse the XML, and look for a target
        // URL.
        $openurl_base = 'https://bc.alma.exlibrisgroup.com/view/uresolver/01BC_INST/openurl';
        $query = http_build_query([
            'svc_dat' => 'CTO',
            'debug' => 'true',
            'rft.mms_id' => $mms
        ]);
        $contents = file_get_contents("$openurl_base?$query");
        $xml = new SimpleXMLElement($contents);
        $xml->registerXPathNamespace('u', 'http://com/exlibris/urm/uresolver/xmlbeans/u');
        $target_links = $xml->xpath('//u:target_url');

        // No target URL? Return null.
        if (count($target_links) === 0) {
            return null;
        }

        $target_link = $target_links[0];

        // If it's Hathi, get the user to log in.
        if (str_contains($target_link, 'hathitrust') && !str_contains($target_link, 'bc.edu')) {
            $target_link = $target_link . ';signon=swle:https://login.bc.edu/idp/shibboleth';
        }

        return $target_link;
    }
}
