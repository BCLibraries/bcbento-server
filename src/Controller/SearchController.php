<?php

namespace App\Controller;

use App\Entity\CatalogRecord;
use App\Entity\CatalogSearchResult;
use App\Service\SortTypeEnum;
use BCLib\PrimoClient\PrimoClient;
use BCLib\PrimoClient\SearchResponse;
use TheCodingMachine\GraphQLite\Annotations\Query;

/**
 * Class SearchController
 *
 * Here is some controller documentation.
 *
 * @package App\Controller
 */
class SearchController
{
    /**
     * @var PrimoClient
     */
    private $client;

    public function __construct(PrimoClient $client)
    {
        $this->client = $client;
    }

    /**
     * Search the catalog by keyword
     *
     * @Query
     */
    public function searchCatalog(string $keyword, string $sort_by): SearchResponse
    {
        return $this->client->search($keyword);
    }

}