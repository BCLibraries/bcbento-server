<?php

namespace App\Entity;

use App\Service\LoanMonitor\Availability;
use BCLib\LibKeyClient\LibKeyResponse;
use BCLib\PrimoClient\Doc;
use BCLib\PrimoClient\Link;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * Extended doc for videos to handle screen caps
 *
 * @Type()
 */
class CatalogItem extends Doc
{
    protected ?string $screen_cap = null;

    protected ?string $full_text_url = null;

    protected ?LibKeyResponse $libkey_availability;

    protected ?string $hathitrust_url = null;

    protected ?Availability $availability;

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
        foreach ($parent_props as $key => $value) {
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
     * @return string[]
     */
    public function getMms(): array
    {
        return $this->mms;
    }

    /**
     * @Field()
     */
    public function getLinkToFindingAid(): ?Link
    {
        $pnx = $this->pnx('display', 'lds34');

        if (!isset($pnx[0])) {
            return null;
        }

        return new Link('Finding aid', $pnx[0], 'Finding aid');
    }

    /**
     * @Field()
     */
    public function getDOI(): string
    {
        $pnx = $this->pnx('addata', 'doi');
        return $pnx[0] ?? null;
    }

    /**
     * @Field()
     */
    public function getFullTextUrl(): ?string
    {
        return $this->full_text_url;

    }

    public function setFullTextUrl(?string $url): void
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

    /**
     * @Field()
     */
    public function getHathitrustUrl(): ?string
    {
        return $this->hathitrust_url;
    }

    /**
     * @param string|null $hathitrust_url
     * @return CatalogItem
     */
    public function setHathitrustUrl(?string $hathitrust_url): CatalogItem
    {
        $this->hathitrust_url = $hathitrust_url;
        return $this;
    }

    /**
     * @Field()
     */
    public function getAvailability(): ?Availability
    {
        return $this->availability;
    }

    public function setAvailability(?Availability $availability): void
    {
        $this->availability = $availability;
    }

    /**
     * @Field()
     * @return string[]
     */
    public function getSourceIds(): array
    {
        return $this->pnx('control', 'sourceid');
    }

    /**
     * @Field()
     * @return string[]
     */
    public function getSourceRecordIds(): array
    {
        return $this->pnx('control', 'sourcerecordid');
    }

    /**
     * Get an ID you can build a link to primo with
     *
     * Primo links are built using the first source record ID. For most records, this is the same as the
     * regular ID. For records with more than one source record (i.e. deduplicated records), we'll take
     * the first source record ID.
     *
     * @Field()
     */
    public function getLinkableId(): string
    {
        $source_records = $this->getSourceRecordIds();
        return count($source_records) > 1 ? array_key_first($source_records) : $this->id;
    }
}