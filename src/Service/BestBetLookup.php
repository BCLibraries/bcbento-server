<?php

namespace App\Service;

use App\Entity\LocalBestBet;
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
     * @return LocalBestBet|null
     */
    public function lookup(string $keyword): ?LocalBestBet
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

            $title = $source['title'] ?? null;
            $display_text = $source['displayText'] ?? null;
            $link = $source['link'] ?? null;

            $best_bet = new LocalBestBet($json_best_bet['_id'], $title, $display_text, $link);
        }

        return $best_bet;
    }
}