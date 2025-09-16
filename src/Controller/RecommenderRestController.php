<?php

namespace App\Controller;

use App\Entity\LibrarianRecommendationResponse;
use App\ReturnTypes\Translator;
use App\Service\LibrarianRecommender;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class RecommenderRestController
{
    private LibrarianRecommender $librarian_recommender;
    private Translator $translator;

    public function __construct(LibrarianRecommender $librarian_recommender, Translator $translator)
    {
        $this->librarian_recommender = $librarian_recommender;
        $this->translator = $translator;
    }

    public function recommendLibrarian(Request $request)
    {
        $keyword = $request->query->get('q');
        /** @var $librarians LibrarianRecommendationResponse */
        $librarians = $this->librarian_recommender->fetchRecommendation($keyword);
        $result = $this->translator->translateLibrariansResult($librarians);
        return new JsonResponse($result);
    }
}
