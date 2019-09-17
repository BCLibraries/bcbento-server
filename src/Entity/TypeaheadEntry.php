<?php

namespace App\Entity;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * A typeahead service response entry
 *
 * @Type()
 */
class TypeaheadEntry implements \JsonSerializable
{
    /**
     * @var string
     */
    private $value;

    /**
     * @var string
     */
    private $type;

    public function __construct(string $value, string $type)
    {
        $this->value = $value;
        $this->type = $type;
    }

    /**
     * @Field()
     */
    public function getValue(): string
    {
        return $this->value;
    }


    /**
     * @Field()
     */
    public function getType(): string
    {
        return $this->type;
    }

    public function jsonSerialize()
    {
        return [
            'value' => $this->value,
            'type' => $this->type
        ];
    }


}