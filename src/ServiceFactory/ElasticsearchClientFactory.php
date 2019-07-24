<?php

namespace App\ServiceFactory;

use Elasticsearch\ClientBuilder;

class ElasticsearchClientFactory
{
    public static function createElasticsearchClient(string $elasticsearch_url): \Elasticsearch\Client
    {
        return ClientBuilder::create()
            ->setHosts([$elasticsearch_url])
            ->build();
    }
}