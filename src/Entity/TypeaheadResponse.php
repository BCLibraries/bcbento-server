<?php

namespace App\Entity;

use JsonSerializable;

/**
 * A complete typeahead service response
 */
class TypeaheadResponse implements JsonSerializable
{
    /**
     * @var TypeaheadEntry[]
     */
    private $entries = [];

    public function addEntry(TypeaheadEntry $entry): void
    {
        $this->entries[] = $entry;
    }

    /**
     * @return TypeaheadEntry[]
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize(): array
    {
        return $this->entries;
    }
}