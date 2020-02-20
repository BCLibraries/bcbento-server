<?php

namespace App\Controller;

use App\Entity\CatalogSearchResponse;
use App\Service\LibKeyService;
use App\Service\PrimoSearch;
use TheCodingMachine\GraphQLite\Annotations\Query;

/**
 * Handle Primo searches
 *
 * Each bento box that shows results from Primo (catalog, articles, videos) has a separate
 * method.
 *
 * @package App\Controller
 */
class PrimoSearchController
{
    /**
     * @var PrimoSearch
     */
    private $primo_search;

    /**
     * @var LibKeyService
     */
    private $libkey;

    public function __construct(PrimoSearch $primo_search, LibKeyService $libkey)
    {
        $this->primo_search = $primo_search;
        $this->libkey = $libkey;
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
        $result = $this->primo_search->searchArticle($keyword, $limit);
        $this->libkey->addLibKeyAvailability($result->getDocs());
        return $result;
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