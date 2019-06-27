<?php

namespace App\Entity;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * @Type()
 */
class FAQResponse
{
    /**
     * @var int
     */
    private $total;

    /**
     * @var string
     */
    private $query;

    /**
     * @var FAQResult[]
     */
    private $records;

    /**
     * @var string
     */
    private $error;

    /**
     * @Field()
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): self
    {
        $this->total = $total;
        return $this;
    }


    /**
     * @Field()
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    public function setQuery(string $query): self
    {
        $this->query = $query;
        return $this;
    }

    /**
     * @Field()
     * @return FAQResult[]
     */
    public function getResults(): array
    {
        return $this->records;
    }

    public function setResults(array $records): self
    {
        $this->records = $records;
        return $this;
    }

    /**
     * @Field()
     */
    public function getError(): string
    {
        return $this->error;
    }

    public function setError(string $error): self
    {
        $this->error = $error;
        return $this;
    }

    /**
     * @Field()
     */
    public function getSearchUrl(): string
    {
        $keyword = urlencode($this->query);
        return "https://answers.bc.edu/search/?t=0&q=$keyword";
    }
}