<?php


namespace App\Service;


use BCLib\PrimoClient\Doc;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Met On Demand screencap provider
 *
 * Met On Demand stores information about the video in Open Graph (https://ogp.me/) metadata
 * embedded in the video page. IDs for Met On Demand videos are stored in field lad06 of the
 * PNX record. Get the video HTML page by plugging those IDs into the base Met on Demand URL.
 *
 * @package App\Service
 */
class MetOnDemandScreencapProvider implements ScreencapProvider
{
    use LoadsOpenGraphImages;

    private const MET_ON_DEMAND_URL_BASE = 'https://metopera.org/Season/On-Demand/opera/?upc=';

    /**
     * @var Client
     */
    private $guzzle;

    public function __construct(Client $guzzle)
    {

        $this->guzzle = $guzzle;
    }

    /**
     * Is a doc from Met On Demand?
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
        $link_to_video_page = self::MET_ON_DEMAND_URL_BASE . $met_id;
        return $this->guzzle->getAsync($link_to_video_page, ['allow_redirects' => true])->then(
            function (ResponseInterface $response) use ($doc) {
                return $this->getOpenGraphImage((string)$response->getBody());
            }
        );
    }
}