<?php

namespace App\Controller;

use App\Entity\BestBet;
use App\Service\BestBetLookup;
use TheCodingMachine\GraphQLite\Annotations\Query;

class BestBetController
{
    /**
     * @var BestBetLookup
     */
    private $best_bets;

    public function __construct(BestBetLookup $best_bets)
    {
        $this->best_bets = $best_bets;
    }

    /**
     * @Query()
     */
    public function bestBet(string $keyword): ?BestBet
    {
        return $this->best_bets->lookup($keyword);
    }
}