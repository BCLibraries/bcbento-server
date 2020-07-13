<?php

namespace App\Service\HathiTrust;

use App\Entity\CatalogItem;
use GuzzleHttp\Client;

class HathiClient
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $shib_idp;

    /**
     * @param Client $client
     * @param string|null $shib_idp
     */
    public function __construct(Client $client, string $shib_idp)
    {
        $this->client = $client;
        $this->shib_idp = $shib_idp;
    }

    /**
     * Add HathiTrust links to items that need them
     *
     * @param CatalogItem[] $docs
     */
    public function getHathiLinks(array $docs): void
    {
        $request = new HathiRequest($docs);
        $response = $this->client->get($request->getURL())->getBody()->getContents();

        $response_json = json_decode($response, true);

        $ids = array_keys($response_json);

        foreach ($ids as $id) {
            $match = $response_json[$id];

            foreach ($match['records'] as $record) {
                $request->updateDoc($id, $this->buildRecordURL($record));
            }
        }
    }

    public function buildRecordURL(array $record): string
    {
        return $record['recordURL'] . "?urlappend=%3Bsignon=swle:{$this->shib_idp}";
    }
}