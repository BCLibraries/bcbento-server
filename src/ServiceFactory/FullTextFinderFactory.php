<?php

namespace App\ServiceFactory;

use BCLib\FulltextFinder\Config;
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
        $config = new Config();
        $config->setUserAgent("BCBento/0.1 (https://library.bc.edu/search; mailto:$crossref_mailto)")
            ->setFindByCitationMinLength(50);
        return FullTextFinder::build($libkey_id, $libkey_apikey, $config);
    }
}