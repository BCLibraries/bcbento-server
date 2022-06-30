<?php

namespace App\Service;

use BCLib\PrimoClient\Item;
use Generator;
use GuzzleHttp\Client;
use PHPUnit\Exception;
use SimpleXMLElement;

/**
 * Availability client for Alma
 *
 * Checks availability using the Alma RTA publish_avail service, just like Primo does. Alma
 * provides other ways to check availability, but the (undocumented) publish_avail service
 * is the only one that can do it in a single request.
 *
 * URLs for publish_avail look like:
 *
 *   https://bc.alma.exlibrisgroup.com/view/publish_avail?library=01BC_INST&doc_num=21421261320001021,21349370700001021
 *
 * where doc_num is a comma-separated list of Alma holdings IDs. The service returns an XML document
 * that is something like a concatenation of several OAI-PMH records containing MARCXML. The
 * availability information is in a proprietary MARC field with the field code 'AVA'.
 *
 * @package App\Service
 */
class AlmaClient implements AvailabilityClient
{

    /** @var Client the HTTP client */
    private $client;

    /** @var string Alma host name (e.g. 'alma.exlibris.com') */
    private $alma_host;

    /** @var string Alma library code (e.g. '01BC_INST') */
    private $library;

    public function __construct(Client $client, string $alma_host, string $alma_library)
    {
        $this->client = $client;
        $this->alma_host = $alma_host;
        $this->library = $alma_library;
    }

    /**
     * Check availability for a list of holdings
     *
     * Returns an array of arrays of items, keyed by holding ID, e.g.:
     *
     *     [
     *       '01BC_INST21421261320001021' => [Item1, Item2],
     *       '01BC_INST21349370700001021' => [Item3],
     *       '01BC_INST21482961550001021' => [Item4, Item5, Item6]
     *     ]
     *
     * @param array $holding_ids
     * @return Item[] array of array of items, keyed by holding ID
     */
    public function checkAvailability(array $holding_ids): array
    {
        try {
            // Build a URL to request all holdings and fetch it.
            $holding_ids = array_map([$this, 'cleanHoldingId'], $holding_ids);
            $url = $this->buildUrl($holding_ids);
            $response = $this->client->get($url)->getBody()->getContents();
            $xml = simplexml_load_string($response);

            $all_items = [];
            foreach ($this->readAvailability($xml) as $key => $items) {

                /** @var $item Item */
                foreach ($items as $item) {
                    $item->setHoldingId($key);
                    $array_key = '01BC_INST' . $item->getHoldingId();
                    if (!isset($all_items[$array_key])) {
                        $all_items[$array_key] = [];
                    }
                    $all_items[$array_key][] = $item;
                }

            }
            return $all_items;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Yields tuples of Alma ID => availability information
     *
     * The publish_avail service returns an XML document with one <OAI-PMH/> element for every
     * holding. Item-level records are stored in <record> elements.
     *
     * @param $availability_xml
     * @return Generator yields item_id => Item
     */
    private function readAvailability($availability_xml): ?Generator
    {
        // Look in each <OAI-PMH/> element for records.
        foreach ($availability_xml->{'OAI-PMH'} as $oai) {

            // The <record> <header> element contains an item-level identifier formatted like
            // 'aleph_publish:21349370700001021'. We're only interested in the number, not the
            // 'aleph_publish' part.
            $key_parts = explode(':', (string)$oai->ListRecords->record->header->identifier);

            // If there is an identifier, parse the record and return it as a key value pair
            // with the item id pointing to the generated Item record.
            $record_xml = simplexml_load_string($oai->ListRecords->record->metadata->record->asXml());
            if (null !== $key_parts && array_key_exists(1, $key_parts)) {
                yield $key_parts[1] => $this->readRecord($record_xml);
            }
        }
    }

    /**
     * Build an Alma publish_avail service URL
     *
     * @param string[] $ids list of holding IDs
     * @return string
     */
    private function buildUrl(array $ids): string
    {
        $query = http_build_query(
            [
                'doc_num' => implode(',', $ids),
                'library' => $this->library
            ]
        );
        return "https://{$this->alma_host}/view/publish_avail?$query";
    }

    /**
     * Read a single publish_avail MARCXML record
     *
     * @param SimpleXMLElement $record_xml
     * @return Item[]
     */
    private function readRecord(SimpleXMLElement $record_xml): array
    {
        // We are just interested in the subfields of the AVA datafield, so find those and
        // read them.
        $record_xml->registerXPathNamespace('slim', 'http://www.loc.gov/MARC21/slim');
        $avas = $record_xml->xpath('//slim:datafield[@tag="AVA"]');
        return array_map([$this, 'readAVA'], $avas);
    }

    /**
     * Converts a single Alma holding AVA record to an Item
     *
     * AVA fields are proprietary Alma "MARC" fields that contain real-time availability
     * information, e.g.:
     *
     *     <datafield tag="AVA" ind1=" " ind2=" ">
     *         <subfield code="b">ERC</subfield>
     *         <subfield code="c">Stacks</subfield>
     *         <subfield code="d">QL737.C25 G65 2012</subfield>
     *         <subfield code="e">available</subfield>
     *     </datafield>
     *
     * This function maps those subfields to corresponding properties of an Item
     * object.
     *
     * @param SimpleXMLElement $ava_xml
     * @return Item
     */
    private function readAVA(SimpleXMLElement $ava_xml): Item
    {
        $item = new Item();

        // Map the AVA subfield to the corresponding Item:: function.
        $ava_subfield_map = [
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

            // Lookup the subfield in the subfield map and call the Item:: function
            // that sets the appropriate Item property.
            $code = (string)$sub['code'];
            if (isset($ava_subfield_map[$code])) {
                $value = (string)$sub[0];
                call_user_func($ava_subfield_map[$code], $value);
            }
        }
        return $item;
    }

    /**
     * Strip out extraneous stuff from the holdings ID
     *
     * @param string $holding_id
     * @return string
     */
    private function cleanHoldingId(string $holding_id): string
    {
        return str_replace('01BC_INST', '', $holding_id);
    }
}