<?php

namespace App\Service;

use BCLib\PrimoClient\ApiClient;
use BCLib\PrimoClient\Query;
use BCLib\PrimoClient\QueryConfig;
use BCLib\PrimoClient\QueryFacet;
use BCLib\PrimoClient\SearchRequest;
use BCLib\PrimoClient\SearchTranslator;

class VideoSearch
{
    /**
     * @var QueryConfig
     */
    private $query_config;
    /**
     * @var ApiClient
     */
    private $client;

    public function __construct(QueryConfig $books_query_config, ApiClient $client)
    {
        $this->query_config = $books_query_config;
        $this->client = $client;
    }

    public function search(string $keyword, int $limit)
    {
        $query = new Query(Query::FIELD_ANY, Query::PRECISION_CONTAINS, $keyword);
        $request = new SearchRequest($query, $this->query_config);
        $request->limit($limit);

        // @TODO change to use Primo sandbox after API issue fixed
        $video_type_facet = new QueryFacet(QueryFacet::CATEGORY_RESOURCE_TYPE, QueryFacet::OPERATOR_EXACT, 'video');
        $request = $request->include($video_type_facet);

        $json = $this->client->get($request->url());
        return SearchTranslator::translate($json);
    }
}