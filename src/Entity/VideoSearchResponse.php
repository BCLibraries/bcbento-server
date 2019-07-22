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
class VideoSearchResponse extends SearchResponse
{
    /**
     * VideoSearchResponse constructor
     *
     * Build a VideoSearchResponse from a parent SearchResponse
     *
     * @param SearchResponse $parent
     */
    public function __construct(SearchResponse $parent)
    {
        $parent_props = get_object_vars($parent);
        foreach ($parent_props AS $key => $value) {
            $this->$key = $value;
        }

        $video_docs = [];
        foreach ($parent->getDocs() as $doc) {
            $video_docs[] = new Video($doc);
        }
        $this->setDocs($video_docs);
    }

    /**
     * @Field()
     * @return Video[]
     */
    public function getDocs(): array
    {
        return $this->docs;
    }

    /**
     * @param Video[] $docs
     */
    public function setDocs(array $docs): void
    {
        $this->docs = $docs;
    }
}