<?php

namespace App\Entity;

use App\Service\LoanMonitor\Availability;
use BCLib\LibKeyClient\LibKeyResponse;
use BCLib\PrimoClient\Doc;
use BCLib\PrimoClient\Link;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
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

    /** @var string|null */
    protected $hathitrust_url;

    /** @var Availability|null */
    protected $availability;

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
     */
    public function getMms(): ?string
    {
        $pnx = $this->pnx('search', 'addsrcrecordid');
        return $pnx[0] ?? null;
    }

    /**
     * @Field()
     */
    public function getLinkToFindingAid(): ?Link
    {
        $pnx = $this->pnx('links', 'linktofa');

        if (!isset($pnx[0])) {
            return null;
        }

        // Look for a URL & label in the PNX field value. Return null if anything
        // goes wrong. Return a Link if everything went right.
        //@todo Move link generation into Primo Client
        preg_match('/^\$\$U(.*) \$\$D(.*)$/', $pnx[0], $matches);
        return count($matches) === 3 ? new Link($matches[2], $matches[1], 'Finding aid') : null;
    }

    /**
     * @Field()
     */
    public function getDOI(): ?string
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
     * Primo links are built using the first source record ID. We can get the full source record
     * ID (with the source ID) from the keys of any array field of the PNX record.
     *
     * @Field()
     */
    public function getLinkableId(): string
    {
        $source_records = $this->getSourceRecordIds();
        return array_key_first($source_records);
    }
}