<?php

namespace App\Controller;

use App\Entity\LibrarianRecommendationResponse;
use App\Service\LibrarianRecommender;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use TheCodingMachine\GraphQLite\Annotations\Query;

/**
 * Handles recommender bento boxes
 *
 * Recommenders don't return search results but rather recommendations based on a search
 * query. In other words, a recommender query for "otters" might not retrieve a record
 * with the word "otters" in it, but it should retrieve a record relevant to "otters".
 *
 * Currently the only recommender search we have is for librarians.
 *
 * @package App\Controller
 */
class RecommenderController extends AbstractController
{
    /** @var LibrarianRecommender */
    private $librarian_recommender;

    public function __construct(LibrarianRecommender $librarian_recommender)
    {
        $this->librarian_recommender = $librarian_recommender;
    }

    /**
     * Recommend a librarian
     *
     * @Query()
     */
    public function recommendLibrarian(string $keyword): LibrarianRecommendationResponse
    {
        return $this->librarian_recommender->fetchRecommendation($keyword);
    }
}