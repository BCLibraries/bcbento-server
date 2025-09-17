<?php

namespace App\Controller;

use App\Entity\CatalogSearchResponse;
use App\ReturnTypes\Translator;
use App\Service\HathiTrust\HathiClient;
use App\Service\LibKeyService;
use App\Service\LoanMonitor\LoanMonitorClient;
use App\Service\PrimoSearch;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Search Primo and return results using a normal REST API
 *
 * We are slowly migrating away from GraphQL. This controller searches Primo just like the PrimoSearchController
 * does, but it is a boring REST API instead of a GraphQL one.
 */
class PrimoRestController
{
    private PrimoSearch $primo_search;
    private LibKeyService $libkey;
    private HathiClient $hathi;
    private LoanMonitorClient $loan_monitor;
    private Translator $translator;

    public function __construct(
        PrimoSearch       $primo_search,
        LibKeyService     $libkey,
        HathiClient       $hathi,
        LoanMonitorClient $loan_monitor,
        Translator        $translator
    )
    {
        $this->primo_search = $primo_search;
        $this->libkey = $libkey;
        $this->hathi = $hathi;
        $this->loan_monitor = $loan_monitor;
        $this->translator = $translator;
    }

    public function searchCatalog(Request $request): JsonResponse
    {
        $keyword = $request->query->get('q');
        $limit = $request->query->get('limit', 3);
        $result = $this->primo_search->searchFullCatalog($keyword, $limit);
        $this->addRealTimeAvailability($result);
        $response = $this->translator->translateSearchResult($result);
        return new JsonResponse($response);
    }

    public function searchVideo(Request $request): JsonResponse
    {
        $keyword = $request->query->get('q');
        $limit = $request->query->get('limit', 3);
        $result = $this->primo_search->searchVideo($keyword, $limit);
        $this->addRealTimeAvailability($result);
        $response = $this->translator->translateSearchResult($result);
        return new JsonResponse($response);
    }

    public function searchArticles(Request $request): JsonResponse
    {
        $keyword = $request->query->get('q');
        $limit = $request->query->get('limit', 3);
        $result = $this->primo_search->searchArticle($keyword, $limit);
        $response = $this->translator->translateSearchResult($result);
        return new JsonResponse($response);
    }

    private function addRealTimeAvailability(CatalogSearchResponse $response): void
    {

        $all_mms = [];

        $physical_docs = array_filter($response->getDocs(), function ($doc) {
            return $doc->isPhysical();
        });

        foreach ($physical_docs as $doc) {
            $all_mms = array_merge($all_mms, $doc->getMms());
        }

        if (count($all_mms) === 0) {
            return;
        }

        try {
            $lon_mon_result = $this->loan_monitor->fetch($all_mms);

            foreach ($response->getDocs() as $doc) {
                $mmses = $doc->pnx('search', 'addsrcrecordid');
                $doc->setAvailability($lon_mon_result->bestAvailability($mmses));
            }
        } catch (\Exception $exception) {
            // DO NOTHING
        }
    }
}
