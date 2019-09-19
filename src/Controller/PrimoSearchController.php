<?php

namespace App\Controller;

use App\Entity\CatalogSearchResponse;
use App\Service\PrimoSearch;
use TheCodingMachine\GraphQLite\Annotations\Query;

/**
 * Class PrimoSearchController
 *
 * Here is some controller documentation.
 *
 * @package App\Controller
 */
class PrimoSearchController
{
    /**
     * @var PrimoSearch
     */
    private $primo_search;

    public function __construct(PrimoSearch $primo_search)
    {
        $this->primo_search = $primo_search;
    }

    /**
     * Search the catalog by keyword
     *
     * @Query
     */
    public function searchCatalog(string $keyword, int $limit = 3): CatalogSearchResponse
    {
        return $this->primo_search->searchFullCatalog($keyword, $limit);
    }

    /**
     * Search Primo Central by keyword
     *
     * @Query
     */
    public function searchArticles(string $keyword, int $limit = 3): CatalogSearchResponse
    {
        return $this->primo_search->searchArticle($keyword, $limit);
    }

    /**
     * Search Primo videos by keyword
     *
     * @Query
     */
    public function searchVideo(string $keyword, int $limit = 3): CatalogSearchResponse
    {
        return $this->primo_search->searchVideo($keyword, $limit);
    }

}