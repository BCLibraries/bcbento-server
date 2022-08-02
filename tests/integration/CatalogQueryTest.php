<?php

namespace App\Tests\integration;

use App\Testing\QueryBuilder;
use GraphQL\Client;
use PHPUnit\Framework\TestCase;

/**
 * Runs a catalog query against the GraphQL endpoint
 */
class CatalogQueryTest extends TestCase
{
    protected static array|object $results;
    protected static Client $client;
    protected static bool $query_has_been_run = false;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    public function setUp(): void
    {
        parent::setUp();

        // This query is expensive, so only run it once.
        if (!self::$query_has_been_run) {
            $query = QueryBuilder::buildCatalogQuery('otters');
            self::$client = new Client('http://localhost:8000/graphql');
            self::$results = self::$client->runRawQuery($query)->getData()->searchCatalog;
            self::$query_has_been_run = true;
        }
    }

    public function testCatalogQueryReturnsReasonableTotal()
    {
        $this->assertGreaterThan(100, self::$results->total);
        $this->assertLessThan(1000, self::$results->total);
    }

    public function testSearchUrlIsCorrect()
    {
        $url_parts = parse_url(self::$results->searchUrl);
        $this->assertEquals('https', $url_parts['scheme']);
        $this->assertEquals('bc-primo.hosted.exlibrisgroup.com', $url_parts['host']);
        $this->assertEquals('/primo-explore/search', $url_parts['path']);

        // Test that the query string works. We can't be sure of order of its components, so we'll
        // have to compare as arrays.
        $expected_query = [
            'query'        => 'any,contains,otters',
            'tab'          => 'bcl_only',
            'search_scope' => 'bcl',
            'vid'          => 'bclib_new',
            'lang'         => 'en_US',
            'offset'       => 0
        ];
        $query_parts = [];
        parse_str($url_parts['query'], $query_parts);
        $this->assertEquals($expected_query, $query_parts);
    }

    public function testCatalogQueryReturnsThreeDocs()
    {
        $this->assertCount(3, self::$results->docs);
    }

    public function testDocsHaveReasonableValues()
    {
        $possible_types = [
            'Article',
            'Audio',
            'Book',
            'Data',
            'Database',
            'Government document',
            'Image',
            'Journal',
            'Musical recording',
            'Newspaper article',
            'Reference entry',
            'Review',
            'Thesis',
            'Video',
        ];

        foreach (self::$results->docs as $doc) {
            $this->assertStringContainsStringIgnoringCase('otter', $doc->title);
            $this->assertIsString($doc->creator);
            $this->assertIsArray($doc->contributors);
            $this->assertStringStartsWith('ALMA-BC', $doc->linkableId);
            $this->assertMatchesRegularExpression('/\d{4}/', $doc->date);
            $this->assertMatchesRegularExpression('/\d{17}/', $doc->mms);
            $this->assertIsBool($doc->available);
            $this->assertIsBool($doc->isPhysical);
            $this->assertIsBool($doc->isElectronic);
            $this->assertContains($doc->type, $possible_types);
        }
    }

    public function testCoverImagesHaveReasonableValues()
    {
        foreach (self::$results->docs as $doc) {
            foreach ($doc->coverImages as $coverImage) {

                // Cover images should be URLs or the string 'no_value'.
                $is_reasonable = filter_var($coverImage->url, FILTER_VALIDATE_URL) || $coverImage->url === 'no_cover';
                $this->assertTrue($is_reasonable);
            }
        }
    }

    /**
     * @testdox DidUMean works
     */
    public function testDidUMeanWorks()
    {
        $results = self::$client->runRawQuery(QueryBuilder::buildCatalogQuery('ottters'))->getData();
        $this->assertEquals('otters', $results->searchCatalog->didUMean);
    }
}
