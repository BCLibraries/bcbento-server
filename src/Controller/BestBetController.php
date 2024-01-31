<?php

namespace App\Controller;

use App\Entity\BestBet;
use App\Service\BestBetLookup;
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
    /** @var BestBetLookup */
    private $best_bets;

    public function __construct(BestBetLookup $best_bets)
    {
        $this->best_bets = $best_bets;
    }

    /**
     * Look up best bets
     *
     * @Query()
     */
    public function bestBet(string $keyword): ?BestBet
    {

        // Look in Elasticsearch for local best bets first.
        $best_bet_query_result = $this->best_bets->lookup($keyword);
        if ($best_bet_query_result !== null) {
            return $best_bet_query_result;
        }

        // No best bets found.
        return null;
    }
}
