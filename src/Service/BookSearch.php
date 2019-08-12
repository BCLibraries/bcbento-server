<?php

namespace App\Service;

use App\Entity\Item;
use BCLib\PrimoClient\ApiClient;
use BCLib\PrimoClient\Doc;
use BCLib\PrimoClient\Holding;
use BCLib\PrimoClient\Query;
use BCLib\PrimoClient\QueryConfig;
use BCLib\PrimoClient\SearchRequest;
use BCLib\PrimoClient\SearchResponse;
use BCLib\PrimoClient\SearchTranslator;

class BookSearch
{
    /**
     * @var QueryConfig
     */
    private $query_config;
    /**
     * @var ApiClient
     */
    private $client;

    /**
     * @var AlmaClient
     */
    private $alma;

    public function __construct(QueryConfig $books_query_config, ApiClient $client, AlmaClient $alma)
    {
        $this->query_config = $books_query_config;
        $this->client = $client;
        $this->alma = $alma;
    }

    public function search(string $keyword, int $limit): SearchResponse
    {
        $query = new Query(Query::FIELD_ANY, Query::PRECISION_CONTAINS, $keyword);
        $request = new SearchRequest($this->query_config, $query);
        $request->limit($limit);

        $json = $this->client->get($request->url());
        $results = SearchTranslator::translate($json);

        $this->updateRealTimeAvailability($results);

        return $results;
    }

    /**
     * Update holdings records to reflect current availability
     *
     * Holdings records from Primo API Bib lookups contain availability information that can be out-of-date by up to
     * 24 hours. To get current availability information for non-electronic records we need to send a Real Time
     * Availability (RTA) request to Alma and reconcile those results with the holdings records.
     *
     * @param SearchResponse $results
     */
    protected function updateRealTimeAvailability(SearchResponse $results): void
    {
        $physical_docs = array_filter($results->getDocs(), function ($doc) {
            return $doc->isPhysical();
        });

        $holding_ids = $this->getHoldingIdsForRTA($physical_docs);
        $items = $this->alma->checkAvailability($holding_ids);
        $this->reconcileRealTimeAvailability($physical_docs, $items);
    }

    /**
     * Populate a holding with values taken from a real-time lookup
     *
     * @param Holding $holding
     * @param Item $item
     */
    protected function populateHolding(Holding $holding, Item $item): void
    {
        $holding->setAvailabilityStatus($item->getAvailability());
        $holding->setLocationCode($item->getLocationCode());
        $holding->setLocationDisplay($item->getLocation());
        $holding->setLibraryCode($item->getLibrary());
    }

    /**
     * Build array of holding IDs to check for real-time-availability
     *
     * @param array $physical_docs
     * @return Doc[]
     */
    protected function getHoldingIdsForRTA(array $physical_docs): array
    {
        $holding_ids = [];
        foreach ($physical_docs as $doc) {
            foreach ($doc->getHoldings() as $holding) {
                $holding_ids[] = $holding->getIlsId();
            }
        }
        return $holding_ids;
    }

    /**
     * Reconcile Doc holding records with Real Time Availability
     *
     * @param Doc[] $physical_docs
     * @param Item[] $items
     */
    protected function reconcileRealTimeAvailability(array $physical_docs, array $items): void
    {
        foreach ($physical_docs as $doc) {
            foreach ($doc->getHoldings() as $holding) {
                $holding_id = $holding->getIlsId();
                if ($items[$holding_id]) {
                    $this->populateHolding($holding, $items[$holding_id]);
                }
            }
        }
    }
}
