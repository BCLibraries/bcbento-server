<?php

namespace App\Controller;

use App\Entity\FAQResponse;
use App\Service\FAQSearch;
use TheCodingMachine\GraphQLite\Annotations\Query;

class FAQSearchController
{
    /**
     * @var FAQSearch
     */
    private $faq_search;

    public function __construct(FAQSearch $faq_search)
    {
        $this->faq_search = $faq_search;
    }

    /**
     * Search LibAnswers by keyword
     *
     * @Query()
     */
    public function searchFAQ(string $keyword, int $limit = 3): FAQResponse
    {
        return $this->faq_search->search($keyword, $limit);
    }
}