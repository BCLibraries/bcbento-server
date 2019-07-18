<?php

namespace App\Controller;

use App\Entity\FAQResponse;
use App\Service\ArticleSearch;
use App\Service\BookSearch;
use App\Service\FAQSearch;
use App\Service\VideoSearch;
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

    /**
     * @var ArticleSearch
     */
    private $article_search;

    /**
     * @var FAQSearch
     */
    private $faq_search;

    /**
     * @var VideoSearch
     */
    private $video_search;

    public function __construct(
        BookSearch $book_search,
        ArticleSearch $article_search,
        FAQSearch $faq_search,
        VideoSearch $video_search
    ) {
        $this->book_search = $book_search;
        $this->article_search = $article_search;
        $this->faq_search = $faq_search;
        $this->video_search = $video_search;
    }

    /**
     * Search the catalog by keyword
     *
     * @Query
     */
    public function searchCatalog(string $keyword, int $limit = 3): SearchResponse
    {
        return $this->book_search->search($keyword, $limit);
    }

    /**
     * @Query
     */
    public function searchArticles(string $keyword, int $limit = 3): SearchResponse
    {
        return $this->article_search->search($keyword, $limit);
    }

    /**
     * @Query
     */
    public function searchFAQ(string $keyword, int $limit = 3): FAQResponse
    {
        return $this->faq_search->search($keyword, $limit);
    }

    /**
     * @Query
     */
    public function searchVideo(string $keyword, int $limit = 3): SearchResponse
    {
        return $this->video_search->search($keyword, $limit);
    }

}