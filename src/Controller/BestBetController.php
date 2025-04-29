<?php

namespace App\Controller;

use App\Entity\BestBet;
use App\Service\BestBetLookup;
use Symfony\Component\HttpFoundation\JsonResponse;
use TheCodingMachine\GraphQLite\Annotations\Query;
use Symfony\Component\HttpFoundation\Request;


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

    public function restBestBet(Request $request): JsonResponse
    {
        $keyword = $request->query->get('q');
        $best_bet_query_result = $this->best_bets->lookup($keyword);
        if ($best_bet_query_result === null) {
            return new JSONResponse(null);
        }
        return new JSONResponse([
            'id' => $best_bet_query_result->getId(),
            'title' => $best_bet_query_result->getTitle(),
            'display_text' => $best_bet_query_result->getDisplayText(),
            'link' => $best_bet_query_result->getLink()
        ]);
    }
}
