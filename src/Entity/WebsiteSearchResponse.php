<?php

namespace App\Entity;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * @Type()
 */
class WebsiteSearchResponse
{
    /**
     * @var int
     */
    private $total;

    /**
     * @var Webpage[]
     */
    private $docs = [];

    /**
     * @var string
     */
    private $query;

    public function __construct(int $total, string $query)
    {
        $this->total = $total;
        $this->query = $query;
    }

    /**
     * @Field()
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * @Field()
     * @return Webpage[]
     */
    public function getDocs(): array
    {
        return $this->docs;
    }

    /**
     * @Field()
     */
    public function getSearchUrl(): string
    {
        $keyword = urlencode($this->query);
        return "http://libguides.bc.edu/srch.php?q=$keyword";
    }

    public function addDoc(Webpage $webpage): void
    {
        $this->docs[] = $webpage;
    }
}