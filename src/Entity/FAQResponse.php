<?php

namespace App\Entity;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * @Type()
 */
readonly class FAQResponse
{
    /** @Field */
    public readonly int $total;

    /** @Field */
    public readonly string $query;

    /**
     * @Field
     * @var FAQResult[]
     */
    public readonly array $results;

    public function __construct(int $total, string $query, array $docs = [])
    {
        $this->total = $total;
        $this->query = $query;
        $this->results = $docs;
    }
}
