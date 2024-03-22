<?php

namespace App\ReturnTypes;

readonly class PrimoDoc
{
    public function __construct(
        public string $type,
        public string $id,
        public string $linkableId,
        public string $title,
        public string $creator,
        public string $mms,
        /** @type string[] */
        public array $contributors = [],
        public ?string $publisher = null,
        public ?string $date = null,
        public ?string $findingAidUrl = null,
        public bool $isPhysical = true,
        public bool $isElectronic = false,
        /** @type string[] */
        public array $coverImages = [],
        public ?string $screenCap = null,
        /** @type Holding[] */
        public array $holdings = [],
        public ?Availability $availability = null
    ) {
    }
}
