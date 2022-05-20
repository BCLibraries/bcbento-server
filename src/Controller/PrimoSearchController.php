<?php

namespace App\Controller;

use App\Entity\CatalogSearchResponse;
use App\Service\HathiTrust\HathiClient;
use App\Service\LibKeyService;
use App\Service\LoanMonitor\LoanMonitorClient;
use App\Service\PrimoSearch;
use TheCodingMachine\GraphQLite\Annotations\Query;

/**
 * Handle Primo searches
 *
 * Each bento box that shows results from Primo (catalog, articles, videos) has a separate
 * method.
 *
 * @package App\Controller
 */
class PrimoSearchController
{
    /**
     * @var PrimoSearch
     */
    private $primo_search;

    /**
     * @var LibKeyService
     */
    private $libkey;

    /**
     * @var HathiClient
     */
    private $hathi;

    private LoanMonitorClient $loan_monitor;

    public function __construct(PrimoSearch       $primo_search,
                                LibKeyService     $libkey,
                                HathiClient       $hathi,
                                LoanMonitorClient $loan_monitor)
    {
        $this->primo_search = $primo_search;
        $this->libkey = $libkey;
        $this->hathi = $hathi;
        $this->loan_monitor = $loan_monitor;
    }

    /**
     * Search the catalog by keyword
     *
     * @Query
     */
    public function searchCatalog(string $keyword, int $limit = 3): CatalogSearchResponse
    {
        $result = $this->primo_search->searchFullCatalog($keyword, $limit);
        try {
            $this->hathi->getHathiLinks($result->getDocs());
        } catch (\Exception $e) {

        }
        $this->addRealTimeAvailability($result);
        return $result;
    }

    /**
     * Search online resources by keyword
     *
     * @Query
     */
    public function searchOnline(string $keyword, int $limit = 3): CatalogSearchResponse
    {
        $result = $this->primo_search->searchOnlineResources($keyword, $limit);
        try {
            $this->hathi->getHathiLinks($result->getDocs());
        } catch (\Exception $e) {

        }
        return $result;
    }

    /**
     * Search Primo Central by keyword
     *
     * @Query
     */
    public function searchArticles(string $keyword, int $limit = 3): CatalogSearchResponse
    {
        $result = $this->primo_search->searchArticle($keyword, $limit);
        $this->libkey->addLibKeyAvailability($result->getDocs());
        return $result;
    }

    /**
     * Search Primo videos by keyword
     *
     * @Query
     */
    public function searchVideo(string $keyword, int $limit = 3): CatalogSearchResponse
    {
        $result = $this->primo_search->searchVideo($keyword, $limit);
        $this->addRealTimeAvailability($result);
        return $result;
    }

    private function addRealTimeAvailability(CatalogSearchResponse $response)
    {

        $all_mms = [];

        $physical_docs = array_filter($response->getDocs(), function ($doc) {
            return $doc->isPhysical();
        });

        foreach ($physical_docs as $doc) {
            $all_mms = array_merge($all_mms, $doc->pnx('search', 'addsrcrecordid'));
        }

        if (count($all_mms) === 0) {
            return;
        }

        $lon_mon_result = $this->loan_monitor->fetch($all_mms);

        foreach ($response->getDocs() as $doc) {
            $mmses = $doc->pnx('search', 'addsrcrecordid');
            $doc->setAvailability($lon_mon_result->bestAvailability($mmses));
        }
    }

}