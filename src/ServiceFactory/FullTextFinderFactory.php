<?php

namespace App\ServiceFactory;

use BCLib\FulltextFinder\FullTextFinder;

/**
 * Static function for building a full text finder
 *
 * Arguments are defined as ENV variables and bound in services.yaml.
 *
 * @package App\ServiceFactory
 */
class FullTextFinderFactory
{
    public static function createFullTextFinder(
        string $libkey_id,
        string $libkey_apikey,
        string $crossref_mailto
    ): FullTextFinder {
        $crossref_user_agent = "BCBento/0.1 (https://library.bc.edu/search; mailto:$crossref_mailto)";
        return FullTextFinder::build($libkey_id, $libkey_apikey, $crossref_user_agent);
    }
}