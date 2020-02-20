<?php

namespace App\Service;

use BCLib\PrimoClient\Item;
use Generator;
use SimpleXMLElement;

interface AvailabilityClient
{
    /**
     * Check availability for a list of holdings
     *
     * @param array $holding_ids
     * @return Item[]
     */
    public function checkAvailability(array $holding_ids): array;
}