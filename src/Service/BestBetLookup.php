<?php

namespace App\Service;

use App\Entity\BestBet;
use Elasticsearch\Client as ElasticsearchClient;

class BestBetLookup
{
    /**
     * @var ElasticsearchClient
     */
    private $elasticsearch;

    public function __construct(ElasticsearchClient $elasticsearch)
    {
        $this->elasticsearch = $elasticsearch;
    }

    /**
     * @param string $keyword
     * @return BestBet|null
     */
    public function lookup(string $keyword): ?BestBet
    {
        $params = [
            'index' => 'bestbets',
            'type' => 'bestbet',
            'body' => [
                'query' => [
                    'match' => [
                        'terms' => $keyword
                    ]
                ]
            ]
        ];

        $result = $this->elasticsearch->search($params);

        $best_bet = null;

        if ($result['hits']['total'] > 0) {
            $json_best_bet = $result['hits']['hits'][0];
            $source = $json_best_bet['_source'];
            $best_bet = new BestBet($json_best_bet['_id'], $source['title'], $source['displayText']);
        }

        return $best_bet;
    }
}