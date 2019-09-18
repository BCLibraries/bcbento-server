<?php

namespace App\Controller;

use App\Entity\LibrarianRecommendationResponse;
use App\Service\LibrarianRecommender;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use TheCodingMachine\GraphQLite\Annotations\Query;

class RecommenderController extends AbstractController
{
    /**
     * @var LibrarianRecommender
     */
    private $librarian_recommender;

    public function __construct(LibrarianRecommender $librarian_recommender)
    {
        $this->librarian_recommender = $librarian_recommender;
    }

    /**
     * @Query()
     */
    public function recommendLibrarian(string $keyword): LibrarianRecommendationResponse
    {
        return $this->librarian_recommender->fetch($keyword);
    }
}