<?php

namespace App\Service;

use App\Entity\VideoSearchResponse;
use BCLib\PrimoClient\ApiClient;
use BCLib\PrimoClient\Query;
use BCLib\PrimoClient\QueryConfig;
use BCLib\PrimoClient\QueryFacet;
use BCLib\PrimoClient\SearchRequest;
use BCLib\PrimoClient\SearchTranslator;
use GuzzleHttp\Client;

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

    /**
     * @var VideoThumbService
     */
    private $video_thumbs;

    public function __construct(QueryConfig $books_query_config, ApiClient $client, VideoThumbService $video_thumbs)
    {
        $this->query_config = $books_query_config;
        $this->client = $client;

        $this->video_thumbs = $video_thumbs;
        $this->video_thumbs->addProvider(new MediciTVVideoProvider(new Client()));
        $this->video_thumbs->addProvider(new MetOnDemandVideoProvider(new Client()));
    }

    public function search(string $keyword, int $limit): VideoSearchResponse
    {
        $query = new Query(Query::FIELD_ANY, Query::PRECISION_CONTAINS, $keyword);
        $request = new SearchRequest($this->query_config, $query);
        $request->limit($limit);

        // @TODO change to use Primo sandbox after API issue fixed
        $video_type_facet = new QueryFacet(QueryFacet::CATEGORY_RESOURCE_TYPE, QueryFacet::OPERATOR_EXACT, 'video');
        $request = $request->include($video_type_facet);

        $json = $this->client->get($request->url());
        $result = new VideoSearchResponse(SearchTranslator::translate($json));

        $this->video_thumbs->fetch($result);
        return $result;
    }
}