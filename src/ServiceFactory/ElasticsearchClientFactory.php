<?php

namespace App\ServiceFactory;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;

class ElasticsearchClientFactory
{
    public static function createElasticsearchClient(string $elasticsearch_url): Client
    {
        return ClientBuilder::create()
            ->setHosts([$elasticsearch_url])
            ->build();
    }
}
