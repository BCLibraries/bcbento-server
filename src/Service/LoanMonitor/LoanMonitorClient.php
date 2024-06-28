<?php

namespace App\Service\LoanMonitor;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Client for reading availability through the Loan Monitor
 *
 * The Loan Monitor is a BC Web service that tracks realtime availability of physical items in the
 * Libraries. It takes MMS IDs as input and returns a list of holdings for each MMS.
 */
class LoanMonitorClient
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function fetch(array $mms_ids)
    {
        if (count($mms_ids) === 0) {
            return new Response("");
        }

        $mmses = join('+', $mms_ids);
        $url = "https://library.bc.edu/availability-monitor/bib/$mmses";
        $response = $this->client->request('GET', $url);
        $content = $response->getContent();
        return new Response($response->getContent());
    }
}
