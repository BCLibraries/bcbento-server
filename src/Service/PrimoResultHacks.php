<?php

namespace App\Service;

use App\Entity\CatalogItem;

/**
 * Do hacky stuff to result sets
 *
 * Sometimes we can't rely on Primo to return results in the right order, so we have to hack
 * the result set and re-order items. I'm not proud of this.
 *
 * @package App\Service
 */
class PrimoResultHacks
{
    // Associative array of MMS ids of items to swap. The key is the bad record, the
    // value is the good record.
    public const PAIRS = [
        '99137813822601021' => '99137911879401021' // Tattoos on the Heart
    ];

    /**
     * Run hacks to promote good records over bad
     *
     * @param CatalogItem[] $docs
     * @return CatalogItem[]
     */
    public static function runHacks(array $docs): array
    {
        $mmses = array_map(static function (CatalogItem $doc) {
            return $doc->getMms();
        }, $docs);

        $i = 0;
        foreach ($mmses as $mms) {
            if ($to_swap = self::getResultToSwapWith($mms, $mmses, $i)) {
                $temp = $docs[$i];
                $docs[$i] = $docs[$to_swap];
                $docs[$to_swap] = $temp;
            }
            $i++;
        }
        return $docs;
    }

    /**
     * Get the array index of item to swap with
     *
     * @param $mms
     * @param array $mmses
     * @param int $current_index
     * @return bool|int array index or false if no swap candidate found
     */
    private static function getResultToSwapWith($mms, array $mmses, int $current_index)
    {
        // If the record in question isn't bad, return false.
        if (!isset(self::PAIRS[$mms])) {
            return false;
        }

        // Look through the MMS list for the good record MMS.
        $key = array_search(self::PAIRS[$mms], $mmses, true);

        // If the good record MMS wasn't found or if it is already promoted, return false.
        if ($key === false || $key < $current_index) {
            return false;
        }

        return $key;
    }
}