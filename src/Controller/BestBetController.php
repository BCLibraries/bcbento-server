<?php

namespace App\Controller;

use App\Entity\BestBet;
use App\Service\BestBetLookup;
use BCLib\FulltextFinder\FullTextFinder;
use TheCodingMachine\GraphQLite\Annotations\Query;

class BestBetController
{
    /**
     * @var BestBetLookup
     */
    private $best_bets;

    /**
     * @var FullTextFinder
     */
    private $fulltext_finder;

    public function __construct(BestBetLookup $best_bets, FullTextFinder $fulltext_finder)
    {
        $this->best_bets = $best_bets;
        $this->fulltext_finder = $fulltext_finder;
    }

    /**
     * @Query()
     */
    public function bestBet(string $keyword): ?BestBet
    {
        $best_bet_query_result = $this->best_bets->lookup($keyword);
        if ($best_bet_query_result !== null) {
            return $best_bet_query_result;
        }

        $fulltext_query_result = $this->fulltext_finder->find($keyword);
        if (isset($fulltext_query_result) && $fulltext_query_result->getFullText()) {
            return new BestBet(
                'Citation',
                $fulltext_query_result->getTitle(),
                'We have found the  full text',
                $fulltext_query_result->getFullText()
            );
        }

        return null;
    }
}