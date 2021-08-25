<?php

namespace App\Entity;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;


/**
 * A Best Bet created locally
 *
 * E.g. the record for JSTOR or the New York Times.
 *
 * @Type()
 *
 * @package App\Entity
 */
class LocalBestBet extends BestBet
{
    /**
     * @var string
     */
    private $display_text;

    /**
     * @var string|null
     */
    private $link;

    public function __construct(string $id, string $title, string $display_text, ?string $link) {
        parent::__construct($id, $title);
        $this->display_text = $display_text;
        $this->link = $link;
    }

    /**
     * @Field()
     */
    public function getDisplayText(): ?string
    {
        return $this->display_text;
    }

    public function setDisplayText(string $display_text): void
    {
        $this->display_text = $display_text;
    }

    /**
     * @Field()
     */
    public function getLink(): ?string
    {
        return $this->link ?: null;
    }

    public function setLink(string $link): void
    {
        $this->link = $link;
    }
}