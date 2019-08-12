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

    /**
     * @var string
     */
    private $display_text;

    /**
     * @var string|null
     */
    private $link;

    public function __construct(string $id, string $title, string $display_text, ?string $link)
    {
        $this->id = $id;
        $this->title = $title;
        $this->display_text = $display_text;
        $this->link = $link;
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
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @Field()
     */
    public function getDisplayText(): string
    {
        return $this->display_text;
    }

    /**
     * @Field()
     */
    public function getLink(): string
    {
        return $this->link ? $this->link : null;
    }
}