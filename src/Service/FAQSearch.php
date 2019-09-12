<?php

namespace App\Service;

use App\Entity\FAQResponse;
use App\Entity\FAQResult;

class  FAQSearch
{
    public function search(string $keyword, int $limit)
    {
        $curl = curl_init();
        curl_setopt_array(
            $curl,
            array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $this->url($keyword, $limit),
                CURLOPT_USERAGENT => 'Codular Sample cURL Request',
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT => 30
            )
        );
        $resp = curl_exec($curl);
        curl_close($curl);

        $remote_response = $resp ? $this->buildResponse(json_decode($resp), $keyword) : ['error_code' => 500];

        return $remote_response;
    }

    private function url(string $keyword, int $limit): string
    {
        return "https://api2.libanswers.com/1.0/search/$keyword?iid=45&limit=$limit";
    }

    private function buildResponse($service_json, $keyword)
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

    private function processResult($result_json)
    {
        $result = new FAQResult();
        $result->setId($result_json->id)
            ->setQuestion($result_json->question)
            ->setUrl($result_json->url)
            ->setTopics($result_json->topics);
        return $result;
    }

}