<?php

namespace App\Service;

class OpenURLSerice
{
    public function __construct()
    {
    }

    public function lookup(string $mms) {
        $fallback_url = "http://bclib.bc.edu/libsearch/bc/keyword/$mms";

        $openurl_base = 'https://bc.alma.exlibrisgroup.com/view/uresolver/01BC_INST/openurl';

        // For now, just return the fallback URL.
        // @todo add OpenURL support
        return $fallback_url;
    }
}
