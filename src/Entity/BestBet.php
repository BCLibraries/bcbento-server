<?php

namespace App\Entity;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * @Type()
 */
class BestBet
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $title;

    public function __construct(string $id, ?string $title)
    {
        $this->id = $id;
        $this->title = $title;
    }

    /**
     * @Field()
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @Field()
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }
}