<?php

namespace App\Service\LoanMonitor;

use TheCodingMachine\GraphQLite\Annotations\Field;

class Availability
{
    private string $mms;
    private ?int $available_count;
    private ?int $total_count;
    private string $call_number;
    private string $library_code;
    private string $location_code;
    private string $library_name;
    private string $location_name;
    private bool $other_availabilities = false;

    public function __construct(\stdClass $avail_data)
    {
        $this->mms = $avail_data->mms;
        $this->available_count = $avail_data->available_count;
        $this->total_count = $avail_data->total_count;
        $this->call_number = $avail_data->call_number;
        $this->library_code = $avail_data->location->library_code;
        $this->location_code = $avail_data->location->location_code;
        $this->library_name = $avail_data->location->library_name;
        $this->location_name = $avail_data->location->location_name;
    }

    /**
     * @Field()
     */
    public function getMms(): string
    {
        return $this->mms;
    }

    public function getAvailableCount(): ?int
    {
        return $this->available_count;
    }

    public function getTotalCount(): ?int
    {
        return $this->total_count;
    }

    public function getCallNumber(): string
    {
        return $this->call_number;
    }

    public function getLibraryCode(): string
    {
        return $this->library_code;
    }

    public function getLocationCode(): string
    {
        return $this->location_code;
    }

    public function getLibraryName(): string
    {
        return $this->library_name;
    }

    public function getLocationName(): string
    {
        return $this->location_name;
    }

    /**
     * Are there other locations where this book is available?
     */
    public function getOtherAvailabilities(): bool
    {
        return $this->other_availabilities;
    }

    public function setOtherAvailabilities(bool $other_availabilities): void
    {
        $this->other_availabilities = $other_availabilities;
    }
}