<?php

namespace App\Service;

use App\Entity\FAQResponse;
use App\Entity\FAQResult;
use App\Exceptions\FailedFAQSearchException;

/**
 * Search LibAnswers FAQ
 *
 * We query the LibAnswers FAQ directly rather than through an Elasticsearch index.
 *
 * @package App\Service
 */
class  FAQSearch
{
    private const LIBANSWERS_ID = '45';

    /**
     * Search LibAnswers FAQ
     *
     * @throws FailedFAQSearchException
     */
    public function search(string $keyword, int $limit): FAQResponse
    {
        $libanswers_response_json = $this->queryLibAnswers($keyword, $limit);
        return $this->buildResponse($libanswers_response_json);
    }

    /**
     * @throws FailedFAQSearchException
     */
    private function queryLibAnswers(string $keyword, int $limit): \stdClass
    {
        $curl = curl_init();
        $api_url = $this->buildSearchAPIUrl($keyword, $limit);
        curl_setopt_array(
            $curl,
            [
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL            => $api_url,
                CURLOPT_USERAGENT      => 'BCLibFAQSearch',
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_FOLLOWLOCATION => 1
            ]
        );
        $resp = curl_exec($curl);
        curl_close($curl);

        if (!$resp) {
            throw new FailedFAQSearchException("Searching FAQ failed: $api_url");
        }

        $decoded = json_decode($resp, false);

        if (is_bool($decoded)) {
            throw new FailedFAQSearchException("Error decoding FAQ search result: $resp");
        }

        return $decoded;
    }

    private function buildSearchAPIUrl(string $keyword, int $limit): string
    {
        $keyword = urlencode($keyword);
        return "https://bc.libanswers.com/api/1.0/search/$keyword?iid=" . self::LIBANSWERS_ID . "&limit=$limit";
    }

    private function buildResponse($libanswers_response_json): FAQResponse
    {
        $search_json = $libanswers_response_json->search;
        return new FAQResponse(
            total: $search_json->numFound ?: 0,
            query: $search_json->query ?: '',
            docs: array_map('\App\Service\FAQSearch::buildFAQResult', $search_json->results)
        );
    }

    private static function buildFAQResult(\stdClass $result_json): FAQResult
    {
        return new FAQResult(
            id: $result_json->id,
            question: $result_json->question,
            url: $result_json->url,
            topics: $result_json->topics
        );
    }
}
