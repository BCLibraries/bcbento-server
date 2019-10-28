<?php

namespace App\Entity;

use BCLib\LibKeyClient\LibKeyResponse;
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

    /** @var string|null */
    protected $full_text_url;

    /** @var LibKeyResponse|null */
    protected $libkey_availability;

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
     * @Field()
     */
    public function getMms(): ?string
    {
        $pnx = $this->pnx('search', 'addsrcrecordid');
        return $pnx[0] ?? null;
    }

    /**
     * @Field()
     */
    public function getDOI(): ?string
    {
        $pnx = $this->pnx('addata','doi');
        return $pnx[0] ?? null;
    }

    /**
     * @Field()
     */
    public function getFullTextUrl(): ?string
    {
        return $this->full_text_url;

    }

    public function setFullTextUrl(?string $url):void
    {
        $this->full_text_url = $url;
    }

    /**
     * @Field()
     */
    public function getLibkeyAvailability(): ?LibKeyResponse
    {
        return $this->libkey_availability;
    }

    public function setLibkeyAvailability(?LibKeyResponse $libkey_availability): void
    {
        $this->libkey_availability = $libkey_availability;
    }

}