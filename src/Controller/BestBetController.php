<?php

namespace App\Controller;

use App\Entity\BestBet;
use App\Service\BestBetLookup;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use TheCodingMachine\GraphQLite\Annotations\Query;

/**
 * Look up Best Bets
 *
 * Bets Bets can either be local results added to the Best Bet Elasticsearch index—e.g. JSTOR,
 * the New York Times, etc—or "citation" best bets retrieved by looking up DOIs or citations
 * in Crossref or LibKey. Local best bets are preferred.
 *
 * @package App\Controller
 */
class BestBetController
{
    private BestBetLookup $best_bets;

    public function __construct(BestBetLookup $best_bets)
    {
        $this->best_bets = $best_bets;
    }

    /**
     * Look up best bets
     *
     * @throws ClientResponseException|ServerResponseException
     *
     * @Query()
     */
    public function bestBet(string $keyword): ?BestBet
    {
        return $this->best_bets->lookup($keyword);
    }
}
