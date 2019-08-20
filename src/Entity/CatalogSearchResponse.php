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
}