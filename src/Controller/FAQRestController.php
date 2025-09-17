<?php

namespace App\Controller;

use App\Entity\LibrarianRecommendationResponse;
use App\ReturnTypes\Translator;
use App\Service\FAQSearch;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class FAQRestController
{
    private FAQSearch $faq_search;
    private Translator $translator;

    public function __construct(FAQSearch $faq_search, Translator $translator)
    {
        $this->faq_search = $faq_search;
        $this->translator = $translator;
    }

    public function search(Request $request): JSONResponse
    {
        $keyword = $request->query->get('q');
        $result = $this->faq_search->search($keyword, 3);
        $result = $this->translator->translateFAQResults($result);
        return new JsonResponse($result);
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
