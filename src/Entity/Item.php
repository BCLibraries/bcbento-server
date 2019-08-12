<?php

namespace App\Entity;

class Item
{
    /**
     * @var string
     */
    private $holding_id;

    /**
     * @var string
     */
    private $institution;

    /**
     * @var string
     */
    private $library;

    /**
     * @var string
     */
    private $library_display;

    /**
     * @var string
     */
    private $location;

    /**
     * @var string
     */
    private $call_number;

    /**
     * @var string
     */
    private $availability;

    /**
     * @var string
     */
    private $number;

    /**
     * @var string
     */
    private $number_unavailable;

    /**
     * @var string
     */
    private $location_code;

    /**
     * @var string
     */
    private $multi_volume;

    /**
     * @var string
     */
    private $number_loans;

    public function getHoldingId(): string
    {
        return $this->holding_id;
    }

    /**
     * @param string $holding_id
     * @return Item
     */
    public function setHoldingId(string $holding_id): Item
    {
        $this->holding_id = $holding_id;
        return $this;
    }



    public function getInstitution(): string
    {
        return $this->institution;
    }

    /**
     * @param string $institution
     * @return Item
     */
    public function setInstitution(string $institution): Item
    {
        $this->institution = $institution;
        return $this;
    }

    public function getLibrary(): string
    {
        return $this->library;
    }

    /**
     * @param string $library
     * @return Item
     */
    public function setLibrary(string $library): Item
    {
        $this->library = $library;
        return $this;
    }

    public function getLibraryDisplay(): string
    {
        return $this->library_display;
    }

    /**
     * @param string $library_display
     * @return Item
     */
    public function setLibraryDisplay(string $library_display): Item
    {
        $this->library_display = $library_display;
        return $this;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    /**
     * @param string $location
     * @return Item
     */
    public function setLocation(string $location): Item
    {
        $this->location = $location;
        return $this;
    }

    public function getCallNumber(): string
    {
        return $this->call_number;
    }

    /**
     * @param string $call_number
     * @return Item
     */
    public function setCallNumber(string $call_number): Item
    {
        $this->call_number = $call_number;
        return $this;
    }

    public function getAvailability(): string
    {
        return $this->availability;
    }

    /**
     * @param string $availability
     * @return Item
     */
    public function setAvailability(string $availability): Item
    {
        $this->availability = $availability;
        return $this;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * @param string $number
     * @return Item
     */
    public function setNumber(string $number): Item
    {
        $this->number = $number;
        return $this;
    }

    public function getNumberUnavailable(): string
    {
        return $this->number_unavailable;
    }

    /**
     * @param string $number_unavailable
     * @return Item
     */
    public function setNumberUnavailable(string $number_unavailable): Item
    {
        $this->number_unavailable = $number_unavailable;
        return $this;
    }

    public function getLocationCode(): string
    {
        return $this->location_code;
    }

    /**
     * @param string $location_code
     * @return Item
     */
    public function setLocationCode(string $location_code): Item
    {
        $this->location_code = $location_code;
        return $this;
    }

    public function getMultiVolume(): string
    {
        return $this->multi_volume;
    }

    /**
     * @param string $multi_volume
     * @return Item
     */
    public function setMultiVolume(string $multi_volume): Item
    {
        $this->multi_volume = $multi_volume;
        return $this;
    }

    public function getNumberLoans(): string
    {
        return $this->number_loans;
    }

    /**
     * @param string $number_loans
     * @return Item
     */
    public function setNumberLoans(string $number_loans): Item
    {
        $this->number_loans = $number_loans;
        return $this;
    }
}