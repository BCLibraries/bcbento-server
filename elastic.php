<?php

use Elasticsearch\ClientBuilder;

require 'vendor/autoload.php';

$client = ClientBuilder::create()
    ->setHosts(['http://libdw.bc.edu:9200'])
    ->build();

$best_bets = new \App\Service\BestBetLookup($client);

$result = $best_bets->lookup('scopus');
print_r($result);