<?php

namespace App\Service;

use App\Entity\FAQResponse;
use App\Entity\FAQResult;

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
     * @param string $keyword
     * @param int $limit
     * @return FAQResponse|array
     */
    public function search(string $keyword, int $limit)
    {
        $curl = curl_init();
        curl_setopt_array(
            $curl,
            array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $this->url($keyword, $limit),
                CURLOPT_USERAGENT => 'BCLibFAQSearch',
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT => 30
            )
        );
        $resp = curl_exec($curl);
        curl_close($curl);

        return $resp ? $this->buildResponse(json_decode($resp, false)) : ['error_code' => 500];
    }

    /**
     * Build LibAnswers FAQ search URL
     *
     * @param string $keyword
     * @param int $limit
     * @return string
     */
    private function url(string $keyword, int $limit): string
    {
        return "https://api2.libanswers.com/1.0/search/$keyword?iid=" . self::LIBANSWERS_ID . "&limit=$limit";
    }

    /**
     * Convert LibAnswer's JSON to a user-digestible response
     *
     * @param $service_json
     * @return FAQResponse
     */
    private function buildResponse($service_json): FAQResponse
    {
        $search_json = $service_json->search;
        $response = new FAQResponse();
        $records = array_map([$this, 'processResult'], $search_json->results);

        $response->setTotal($search_json->numFound ?: 0)
            ->setQuery($search_json->query ?: '')
            ->setError($search_json->error ?: '')
            ->setDocs($records ?: []);
        return $response;
    }

    /**
     * Build a single result
     *
     * @param $result_json
     * @return FAQResult
     */
    private function processResult($result_json): FAQResult
    {
        $result = new FAQResult();
        $result->setId($result_json->id)
            ->setQuestion($result_json->question)
            ->setUrl($result_json->url)
            ->setTopics($result_json->topics);
        return $result;
    }

}