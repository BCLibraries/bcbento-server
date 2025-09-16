<?php

namespace App\ReturnTypes;

readonly class Librarian
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $image,
        public string $score,
        public array  $subjects
    )
    {
    }
}
