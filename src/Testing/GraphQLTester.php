<?php

namespace App\Testing;

use GraphQL\Client;
use GraphQL\Query;
use GraphQL\Results;

class GraphQLTester
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function run(Query $query): Results
    {

        $this->client->runRawQuery((string) $query);
        return $this->client->runQuery($query);
    }
}