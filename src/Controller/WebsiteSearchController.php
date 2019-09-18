<?php


namespace App\Controller;

use App\Entity\WebsiteSearchResponse;
use App\Service\WebsiteSearch;
use TheCodingMachine\GraphQLite\Annotations\Query;

class WebsiteSearchController
{
    /**
     * @var WebsiteSearch
     */
    private $website_search;

    public function __construct(WebsiteSearch $website_search)
    {
        $this->website_search = $website_search;
    }

    /**
     * @Query()
     */
    public function searchWebsite(string $keyword): WebsiteSearchResponse
    {
        return $this->website_search->fetch($keyword);
    }
}