<?php

namespace App\ReturnTypes;

readonly class Holding
{
    public function __construct(
        public string $availabilityStatus,
        public ?string $libraryCode = null,
        public ?string $libraryDisplay = null,
        public ?string $locationDisplay= null,
        public ?string $callNumber = null
    ){}
}
