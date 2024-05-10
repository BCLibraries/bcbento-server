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
readonly class LocalBestBet extends BestBet
{
    /** @Field */
    public string $displayText;

    /** @Field */
    public ?string $link;

    public function __construct(
        string $id,
        string $title,
        string $display_text,
        ?string $link = null
    ) {
        parent::__construct($id, $title);
        $this->displayText = $display_text;
        $this->link = $link;
    }
}
