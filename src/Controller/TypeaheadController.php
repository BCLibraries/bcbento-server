<?php

namespace App\Controller;

use App\Service\TypeaheadService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

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
     * Lookup a term in typeahead as GraphQL
     *
     * Preserves backwards compatibility with older typeahead clients.
     *
     * @Route("/typeahead", name="typeahead")
     */
    public function indexAction(Request $request): JsonResponse
    {
        $response = $this->typeahead->fetch($request->get('any'));
        return $this->json($response);
    }
}