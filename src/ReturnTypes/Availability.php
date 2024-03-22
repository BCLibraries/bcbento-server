<?php

namespace App\ReturnTypes;

readonly class Availability
{
    public function __construct(
        public string $libraryName,
        public string $locationName,
        public string $totalCount,
        public string $callNumber,
        public string $otherAvailabilities
    ) {
    }
}
