<?php

namespace App\Service;

use App\Entity\LocalBestBet;
use Elastic\Elasticsearch\Client as ElasticsearchClient;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Http\Promise\Promise;

/**
 * Lookup Best bets
 *
 * Local BestBets (e.g. databases, journals, helpful hints) are stored in Elasticsearch and
 * indexed by keyword. For example, the JSTOR entry is indexed by "jstor", "jstore", and
 * maybe some other things.
 *
 * Best Bets are stored in Elasticsearch using a keyword index, which does case-sensitive exact
 * matching, e.g. "JSTOR" will not match "jstor". Search terms need to be converted to a normalized
 * form: lower-case, no leading or trailing spaces, and no included quotes.
 *
 * @package App\Service
 */
class BestBetLookup
{
    private ElasticsearchClient $elasticsearch;

    public function __construct(ElasticsearchClient $elasticsearch)
    {
        $this->elasticsearch = $elasticsearch;
    }

    /**
     * Lookup best bets for a keyword search
     *
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function lookup(string $keyword): ?LocalBestBet
    {
        $keyword = self::normalizeSearchTerm($keyword);
        $response = $this->queryElasticsearch($keyword);

        if (self::noBestBetsFound($response)) {
            return null;
        }

        $json_best_bet = $response['hits']['hits'][0];
        return new LocalBestBet(
            id: $json_best_bet['_id'],
            title: $json_best_bet['_source']['title'] ?? null,
            display_text: $json_best_bet['_source']['displayText'] ?? null,
            link: $json_best_bet['_source']['link'] ?? null
        );
    }

    private static function normalizeSearchTerm(string $keyword): string
    {
        $keyword = trim($keyword);
        $keyword = str_replace(['"', "'"], '', $keyword);
        return strtolower($keyword);
    }

    /**
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    private function queryElasticsearch(string $keyword): Promise|Elasticsearch
    {
        $query = [
            'index' => 'bestbets',
            'body'  => [
                'query' => [
                    'term' => [
                        'terms.keyword' => $keyword
                    ]
                ]
            ]
        ];
        return $this->elasticsearch->search($query);
    }

    private static function noBestBetsFound(Elasticsearch $response): bool
    {
        return (!$response['hits']['total'] || $response['hits']['total']['value'] < 1);
    }
}
