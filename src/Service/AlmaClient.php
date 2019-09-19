<?php

namespace App\Service;

use BCLib\PrimoClient\Item;
use Generator;
use GuzzleHttp\Client;
use SimpleXMLElement;

class AlmaClient
{

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string Alma host name (e.g. 'alma.exlibris.com')
     */
    private $alma_host;

    /**
     * @var string Alma library code (e.g. '01BC_INST')
     */
    private $library;

    public function __construct(Client $client, string $alma_host, string $alma_library)
    {
        $this->client = $client;
        $this->alma_host = $alma_host;
        $this->library = $alma_library;
    }

    /**
     * @param array $holding_ids
     * @return Item[]
     */
    public function checkAvailability(array $holding_ids): array: array
    {
        $all_items = [];

        $holding_ids = array_map([$this, 'cleanHoldingId'], $holding_ids);
        $url = $this->buildUrl($holding_ids);
        $response = $this->client->get($url)->getBody()->getContents();
        $xml = simplexml_load_string($response);

        /**
         * @var $item Item
         */
        foreach ($this->readAvailability($xml) as $key => $items) {
            foreach ($items as $item) {
                $item->setHoldingId($key);
                $array_key = '01BC_INST' . $item->getHoldingId();
                if (! isset($all_items[$array_key])) {
                    $all_items[$array_key] = [];
                }
                $all_items[$array_key][] = $item;
            }

        }
        return $all_items;
    }

    /**
     * Yields tuples of Alma ID => availability information
     *
     * @param $availability_xml
     * @return Generator
     */
    public function readAvailability($availability_xml): ?Generator
    {
        foreach ($availability_xml->{'OAI-PMH'} as $oai) {
            $key_parts = explode(':', (string)$oai->ListRecords->record->header->identifier);
            $record_xml = simplexml_load_string($oai->ListRecords->record->metadata->record->asXml());
            if (null !== $key_parts && array_key_exists(1, $key_parts)) {
                yield $key_parts[1] => $this->readRecord($record_xml);
            }
        }

    }

    private function buildUrl($ids): string
    {
        $query = http_build_query(
            [
                'doc_num' => implode(',', $ids),
                'library' => $this->library
            ]
        );
        return "http://{$this->alma_host}/view/publish_avail?$query";
    }

    /**
     * Read a set of AVA records
     *
     * @param SimpleXMLElement $record_xml
     * @return Item[]
     */
    public function readRecord(SimpleXMLElement $record_xml): array
    {
        $record_xml->registerXPathNamespace('slim', 'http://www.loc.gov/MARC21/slim');
        $avas = $record_xml->xpath('//slim:datafield[@tag="AVA"]');
        return array_map([$this, 'readAVA'], $avas);
    }

    private function readAVA(SimpleXMLElement $ava_xml): Item
    {
        $item = new Item();

        $ava_map = [
            'a' => [$item, 'setInstitution'],
            'b' => [$item, 'setLibrary'],
            'c' => [$item, 'setLocation'],
            'd' => [$item, 'setCallNumber'],
            'e' => [$item, 'setAvailability'],
            'f' => [$item, 'setNumber'],
            'g' => [$item, 'setNumberUnavailable'],
            'j' => [$item, 'setLocationCode'],
            'k' => [$item, 'setMultiVolume'],
            'p' => [$item, 'setNumberLoans'],
            'q' => [$item, 'setLibraryDisplay']
        ];

        foreach ($ava_xml->subfield as $sub) {
            $code = (string)$sub['code'];
            if (isset($ava_map[$code])) {
                $value = (string)$sub[0];
                call_user_func($ava_map[$code], $value);
            }
        }
        return $item;
    }

    private function cleanHoldingId(string $holding_id): string
    {
        return str_replace('01BC_INST', '', $holding_id);
    }
}