<?php

namespace App\ServiceFactory;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;

class ElasticsearchClientFactory
{
    public static function createElasticsearchClient(string $elasticsearch_url): Client
    {
        return ClientBuilder::create()
            ->setHosts([$elasticsearch_url])
            ->build();
    }
}