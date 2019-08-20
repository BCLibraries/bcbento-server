<?php

namespace App\Entity;

use BCLib\PrimoClient\Doc;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * Extended doc for videos to handle screen caps
 *
 * @Type()
 */
class CatalogItem extends Doc
{
    protected $screen_cap;

    /**
     * CatalogItem constructor
     *
     * Build a CatalogItem from a parent Doc.
     *
     * @param Doc $parent
     */
    public function __construct(Doc $parent)
    {
        $parent_props = get_object_vars($parent);
        foreach ($parent_props AS $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * @Field()
     */
    public function getScreenCap(): ?string
    {
        return $this->screen_cap;
    }

    public function setScreenCap(?string $url): void
    {
        $this->screen_cap = $url;
    }

    /**
     * @Field
     */
    public function getMms(): ?string
    {
        $pnx = $this->pnx('search', 'addsrcrecordid');
        return $pnx[0] ?? null;
    }
}