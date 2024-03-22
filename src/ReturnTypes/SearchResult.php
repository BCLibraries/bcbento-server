<?php

namespace App\ReturnTypes;

readonly class SearchResult
{
    public int $total;

    public function __construct(
        /** @type PrimoDoc[] */
        public array $docs,
        public string $search_url,
        public ?string $didUMean = null
    ) {
        $this->total = sizeof($this->docs);
    }
}
