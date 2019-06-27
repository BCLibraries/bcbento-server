<?php

namespace App\Service;

use BCLib\PrimoClient\ApiClient;
use BCLib\PrimoClient\Query;
use BCLib\PrimoClient\QueryConfig;
use BCLib\PrimoClient\SearchRequest;
use BCLib\PrimoClient\SearchResponse;
use BCLib\PrimoClient\SearchTranslator;

class ArticleSearch
{
    /**
     * @var QueryConfig
     */
    private $query_config;
    /**
     * @var ApiClient
     */
    private $client;

    public function __construct(QueryConfig $article_query_config, ApiClient $client)
    {
        $this->query_config = $article_query_config;
        $this->client = $client;
    }

    public function search(string $keyword, int $limit): SearchResponse
    {
        $query = new Query(Query::FIELD_ANY, Query::PRECISION_CONTAINS, $keyword);
        $request = new SearchRequest($query, $this->query_config);
        $request->limit($limit);

        $json = $this->client->get($request->url());
        return SearchTranslator::translate($json);
    }
}