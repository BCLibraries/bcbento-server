<?php

namespace App\Entity;

use BCLib\PrimoClient\SearchResponse;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * Extended search result for videos to handle screen caps
 *
 * @Type()
 */
class CatalogSearchResponse extends SearchResponse
{
    /**
     * @var string
     */
    private $search_url;

    /**
     * CatalogSearchResponse constructor
     *
     * Build a CatalogSearchResponse from a parent SearchResponse
     *
     * @param SearchResponse $parent
     */
    public function __construct(SearchResponse $parent)
    {
        $parent_props = get_object_vars($parent);
        foreach ($parent_props AS $key => $value) {
            $this->$key = $value;
        }

        $catalog_docs = [];
        foreach ($parent->getDocs() as $doc) {
            $catalog_docs[] = new CatalogItem($doc);
        }
        $this->setDocs($catalog_docs);
    }

    /**
     * @Field()
     * @return CatalogItem[]
     */
    public function getDocs(): array
    {
        return $this->docs;
    }

    /**
     * @param CatalogItem[] $docs
     */
    public function setDocs(array $docs): void
    {
        $this->docs = $docs;
    }

    /**
     * @Field()
     * @return string
     */
    public function getSearchUrl(): string
    {
        return $this->search_url;
    }

    /**
     * @param string $search_url
     * @return CatalogSearchResponse
     */
    public function setSearchUrl(string $search_url): CatalogSearchResponse
    {
        $this->search_url = $search_url;
        return $this;
    }
}