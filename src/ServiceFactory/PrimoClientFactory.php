<?php

namespace App\ServiceFactory;

use BCLib\PrimoClient\PrimoClient;

/**
 * Static functions for building PrimoClients
 *
 * Arguments are defined as ENV variables and bound in services.yaml.
 *
 * @package App\ServiceFactory
 */
class PrimoClientFactory
{
    public static function createCatalogService(
        string $primo_api_gateway,
        string $primo_api_key,
        string $primo_catalog_tab,
        string $primo_vid,
        string $primo_catalog_scope,
        string $primo_inst
    ): PrimoClient {
        return PrimoClient::build(
            $primo_api_gateway,
            $primo_api_key,
            $primo_catalog_tab,
            $primo_vid,
            $primo_catalog_scope,
            $primo_inst);
    }

    public static function createArticleService(
        string $primo_api_gateway,
        string $primo_api_key,
        string $primo_articles_tab,
        string $primo_vid,
        string $primo_articles_scope,
        string $primo_inst
    ): PrimoClient {
        return PrimoClient::build(
            $primo_api_gateway,
            $primo_api_key,
            $primo_articles_tab,
            $primo_vid,
            $primo_articles_scope,
            $primo_inst);
    }
}