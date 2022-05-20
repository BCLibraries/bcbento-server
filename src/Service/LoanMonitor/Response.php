<?php

namespace App\Service\LoanMonitor;

/**
 * A response from the Loan Monitor service
 */
class Response
{
    private array $availabilities = [];

    private const PREFERRED_LIBRARIES = [
        'ONL'   => 5,
        'TML'   => 4,
        'ERC'   => 4,
        'BURNS' => 4,
        'SWK'   => 4,
        'LAW'   => 0
    ];

    private const PREFERRED_LOCATIONS = [
        'STACKS'   => 3,
        'STACK'    => 3,
        'OVER'     => 3,
        'FOLIO'    => 3,
        'STACK_NL' => 2,
        'REF_NL'   => 2,
        'REF'      => 2,
        'OVER_NL'  => 2
    ];

    public function __construct(string $json)
    {
        $response_object = json_decode($json);

        if ($response_object) {
            $this->buildAvailabilitiesFromJson($response_object);
        }
    }

    /**
     * @param array $mms_ids
     * @return Availability[]
     */
    public function allAvailabilities(array $mms_ids): array
    {
        // Build a list of all the possible availabilities. Each MMS can have multiple
        // availabilities.
        $avails_to_consider = [];
        foreach ($mms_ids as $mms_id) {
            if (isset($this->availabilities[$mms_id])) {
                /** @var Availability[] $avails_to_consider */
                $avails_to_consider = array_merge($avails_to_consider, $this->availabilities[$mms_id]);
            }
        }

        return $avails_to_consider;
    }

    /**
     * Return the preferred availability for a set of MMS IDs
     *
     * @param string[] $mms_ids
     * @return Availability|null the preferred availability info, or null if it isn't available
     */
    public function bestAvailability(array $mms_ids): ?Availability
    {
        // Build a list of all the possible availabilities. Each MMS can have multiple
        // availabilities.
        $avails_to_consider = $this->allAvailabilities($mms_ids);

        /** @var ?Availability $best_avail */
        $best_avail = null;
        $total_avail = 0;

        // Check each availability against the best availability so far and choose which one is
        // better. Only accept items that are actually available.
        foreach ($avails_to_consider as $avail) {
            if ($avail->getAvailableCount() > 0) {
                $best_avail = $best_avail ? $this->preferredAvailability($avail, $best_avail) : $avail;
                $total_avail++;
            }
        }

        // If there are multiple good availabilities
        if ($total_avail > 1) {
            $best_avail->setOtherAvailabilities(true);
        }

        return $best_avail;
    }

    /**
     * Decide which of two availabilities is better
     */
    private function preferredAvailability(?Availability $avail_1, ?Availability $avail_2): ?Availability
    {
        // First the cases where one or both availabilities are bad.
        if ($this->availIsBad($avail_1) && $this->availIsBad($avail_2)) {
            return null;
        }

        if ($this->availIsBad($avail_1)) {
            return $avail_2;
        }

        if ($this->availIsBad($avail_2)) {
            return $avail_1;
        }

        // If both are good, score them and choose the best scoring one. Tie goes to
        // $avail_1.
        $avail_1_score = $this->scoreAvailability($avail_1);
        $avail_2_score = $this->scoreAvailability($avail_2);

        if ($avail_2_score > $avail_1_score) {
            return $avail_2;
        }

        return $avail_1;
    }

    /**
     * Score the availability based on library and location
     */
    private function scoreAvailability(Availability $availability): int
    {
        $score = 0;

        if (isset(self::PREFERRED_LOCATIONS[$availability->getLocationCode()])) {
            $score += self::PREFERRED_LOCATIONS[$availability->getLocationCode()];
        }

        if (isset(self::PREFERRED_LIBRARIES[$availability->getLibraryCode()])) {
            $score += self::PREFERRED_LIBRARIES[$availability->getLibraryCode()];
        }

        // Penalize offsite items.
        if (str_contains($availability->getLocationName(), 'ffsite')) {
            $score -= 1;
        }

        return $score;
    }

    /**
     * Return true if the availability just won't work
     */
    private function availIsBad(?Availability $availability): bool
    {
        // If there's no availability info at all, it's bad.
        if (is_null($availability) || $availability->getAvailableCount() === null) {
            return true;
        }

        // If none of the items are actually available, it's bad.
        if ($availability->getAvailableCount() === 0) {
            return true;
        }

        // Otherwise, it's OK.
        return false;
    }

    /**
     * @param $response_object
     * @return void
     */
    private function buildAvailabilitiesFromJson($response_object): void
    {
        foreach ($response_object as $mms => $avail_array) {
            if (!isset($this->availabilities[$mms])) {
                $this->availabilities[$mms] = [];
            }

            foreach ($avail_array as $avail_data) {
                $this->availabilities[$mms][] = new Availability($avail_data);
            }
        }
    }
}