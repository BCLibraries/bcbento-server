<?php

namespace App\Entity;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * @Type()
 */
class LibrarianRecommendationResponse
{
    /**
     * @var Librarian[]
     */
    private $docs;

    /**
     * @Field()
     */
    public function getTotal(): ?int
    {
        return null;
    }

    /**
     * @Field()
     */
    public function getSearchUrl(): ?string
    {
        return null;
    }

    /**
     * @return Librarian[]
     * @Field()
     */
    public function getDocs(): array
    {
        return $this->docs;
    }

    public function addLibrarian(Librarian $librarian): void
    {
        $this->docs[] = $librarian;
    }

}