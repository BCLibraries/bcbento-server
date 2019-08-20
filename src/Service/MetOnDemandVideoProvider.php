<?php


namespace App\Service;


use BCLib\PrimoClient\Doc;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

class MetOnDemandVideoProvider implements VideoProvider
{
    use ParsesForScreenCapsTrait;

    /**
     * @var Client
     */
    private $guzzle;

    public function __construct(Client $guzzle)
    {

        $this->guzzle = $guzzle;
    }

    /**
     * Is a doc from this service?
     *
     * Returns true if a screencap could be grabbed from this service.
     *
     * @param Doc $doc
     * @return bool
     */
    public function test(Doc $doc): bool
    {
        $providers = $doc->pnx('display','lds30');
        return in_array('MET OPERA ON DEMAND', $providers, true);
    }

    /**
     * Get the screencap
     *
     * Returns a promise that when fulfilled returns a screencap or null.
     *
     * @param Doc $doc
     * @return PromiseInterface
     */
    public function getScreenCap(Doc $doc): PromiseInterface
    {
        $met_ids = $doc->pnx('addata','lad06');
        $met_id = $met_ids[0] ?? '';
        $link = "http://metopera.org/Season/On-Demand/opera/?upc=$met_id";
        return $this->guzzle->getAsync($link, ['allow_redirects' => true])->then(
            function (ResponseInterface $response) use ($doc) {
                return $this->getOpenGraphImage((string)$response->getBody());
            }
        );
    }
}