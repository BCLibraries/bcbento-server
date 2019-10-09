<?php

namespace App\Entity;

use BCLib\FulltextFinder\FinderResponse;
use TheCodingMachine\GraphQLite\Annotations\Field;

class CitationBestBet extends BestBet
{
    /**
     * @var FinderResponse
     */
    private $fulltext;

    public function __construct(string $id, ?string $title, FinderResponse $fulltext)
    {
        parent::__construct($id, $title);
        $this->fulltext = $fulltext;
    }

    /**
     * @Field
     */
    public function getFullText(): FinderResponse
    {
        return $this->fulltext;
    }
}