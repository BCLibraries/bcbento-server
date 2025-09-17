<?php

namespace App\ReturnTypes;

readonly class FAQResult
{
    public function __construct(
        public string $id,
        public string $question,
        public string $url,
        public array $topics,
    )
    {
    }
}
