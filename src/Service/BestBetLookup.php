<?php

namespace App\Service;

use App\Entity\LocalBestBet;
use Elastic\Elasticsearch\Client as ElasticsearchClient;
use PHPUnit\Util\Exception;

/**
 * Lookup Best bets
 *
 * Local BestBets (e.g. databases, journals, helpful hints) are stored in Elasticsearch and
 * indexed by keyword. For example, the JSTOR entry is indexed by "jstor", "jstore", and
 * maybe some other things. Matches have to be exact.
 *
 * TODO Add richer indexing possibilities (e.g. regular expressions)
 *
 * @package App\Service
 */
class BestBetLookup
{
    /** @var ElasticsearchClient */
    private $elasticsearch;

    public function __construct(ElasticsearchClient $elasticsearch)
    {
        $this->elasticsearch = $elasticsearch;
    }

    /**
     * Lookup best bets for a keyword search
     *
     * @param string $keyword
     * @return LocalBestBet|null
     */
    public function lookup(string $keyword): ?LocalBestBet
    {
        // Normalize search term
        $keyword = trim($keyword);
        $keyword = str_replace(array('"', "'"), "", $keyword);

        // Build param list and query Elasticsearch
        $params = [
            'index' => 'bestbets',
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

        if ($result['hits']['total'] &&$result['hits']['total']['value'] > 0 ) {
            $json_best_bet = $result['hits']['hits'][0];
            $source = $json_best_bet['_source'];

            $title = $source['title'] ?? null;
            $display_text = $source['displayText'] ?? null;
            $link = $source['link'] ?? null;

            if ($json_best_bet['_id']) {
                $best_bet = new LocalBestBet($json_best_bet['_id'], $title, $display_text, $link);
            }
        }

        return $best_bet;
    }
}
