<?php

namespace App\ReturnTypes;

readonly class Article
{
    public function __construct(
        public string  $type,
        public string  $id,
        public string  $linkableId,
        public string  $title,
        public string  $creator,
        public string $journal_title,
        /** @type string[] */
        public array   $contributors = [],
        public ?string $publisher = null,
        public ?string $date = null,
        /** @type string[] */
        public array   $coverImages = [],
        public ?string $ispartof = null
    )
    {
    }
}
