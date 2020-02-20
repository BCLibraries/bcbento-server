<?php

namespace App\Controller;

use App\Entity\BestBet;
use App\Entity\CitationBestBet;
use App\Service\BestBetLookup;
use BCLib\FulltextFinder\FullTextFinder;
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

    /** @var FullTextFinder */
    private $fulltext_finder;

    public function __construct(BestBetLookup $best_bets, FullTextFinder $fulltext_finder)
    {
        $this->best_bets = $best_bets;
        $this->fulltext_finder = $fulltext_finder;
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

        // No local best bet? Send it to the full text finder.
        $fulltext_query_result = $this->fulltext_finder->find($keyword);
        if (isset($fulltext_query_result) && $fulltext_query_result->getFullText()) {
            return new CitationBestBet('Citation', $fulltext_query_result->getTitle(), $fulltext_query_result);
        }

        // No best bets found.
        return null;
    }
}