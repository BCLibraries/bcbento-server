<?php

namespace App\Controller;

use App\Service\BookSearch;
use BCLib\PrimoClient\SearchRequest;
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
     * @var BookSearch
     */
    private $book_search;

    public function __construct(BookSearch $book_search)
    {
        $this->book_search = $book_search;
    }

    /**
     * Search the catalog by keyword
     *
     * @Query
     */
    public function searchCatalog(string $keyword, int $limit = 5): SearchResponse
    {
        return $this->book_search->search($keyword, $limit);
    }

}