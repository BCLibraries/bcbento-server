<?php

namespace App\Controller;

use App\Service\TypeaheadService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Handle typeahead (autocomplete) queries
 *
 * The typeahead service takes a string as input and returns a JSON result with a list of
 * possible full terms.
 *
 * @package App\Controller
 */
class TypeaheadController extends AbstractController
{
    /**
     * @var TypeaheadService
     */
    private $typeahead;

    public function __construct(TypeaheadService $typeahead)
    {
        $this->typeahead = $typeahead;
    }

    /**
     * Lookup a term in typeahead
     *
     * Returns result as JSON to preserves backwards compatibility with older typeahead clients.
     *
     * @Route("/typeahead", name="typeahead")
     */
    public function indexAction(Request $request): JsonResponse
    {
        // @todo re-enable typeahead when data is reloaded into Elasticsearch
        // $response = $this->typeahead->fetch($request->get('any'));
        return $this->json([]);
    }
}