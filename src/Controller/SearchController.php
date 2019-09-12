<?php

namespace App\Controller;

use App\Entity\ArticleSearchResponse;
use App\Entity\CatalogSearchResponse;
use App\Entity\FAQResponse;
use App\Service\PrimoSearch;
use App\Service\FAQSearch;
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
     * @var PrimoSearch
     */
    private $book_search;

    /**
     * @var FAQSearch
     */
    private $faq_search;


    public function __construct(
        PrimoSearch $book_search,
        FAQSearch $faq_search
    ) {
        $this->book_search = $book_search;
        $this->faq_search = $faq_search;
    }

    /**
     * Search the catalog by keyword
     *
     * @Query
     */
    public function searchCatalog(string $keyword, int $limit = 3): CatalogSearchResponse
    {
        return $this->book_search->searchFullCatalog($keyword, $limit);
    }

    /**
     * Search Primo Central by keyword
     *
     * @Query
     */
    public function searchArticles(string $keyword, int $limit = 3): CatalogSearchResponse
    {
        return $this->book_search->searchArticle($keyword, $limit);
    }

    /**
     * Search LibAnswers by keyword
     *
     * @Query
     */
    public function searchFAQ(string $keyword, int $limit = 3): FAQResponse
    {
        return $this->faq_search->search($keyword, $limit);
    }

    /**
     * Search Primo videos by keyword
     *
     * @Query
     */
    public function searchVideo(string $keyword, int $limit = 3): CatalogSearchResponse
    {
        return $this->book_search->searchVideo($keyword, $limit);
    }

}