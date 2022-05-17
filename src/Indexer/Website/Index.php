<?php

namespace App\Indexer\Website;

use Elasticsearch\Client;
use PHPUnit\Exception;

class Index
{
    private string $website_index_name;
    private Client $elasticsearch;

    public function __construct(string $website_index_name, Client $elasticsearch)
    {
        $this->elasticsearch = $elasticsearch;
        $this->website_index_name = $website_index_name;
    }

    /**
     * Create the Elasticsearch index
     *
     * @return string
     */
    public function create(): string
    {
        $new_index_name = $this->website_index_name . '_' . time();
        $schema_json = file_get_contents(__DIR__ . '/website-schema.json');
        $schema = json_decode($schema_json, true);
        $idx_params = [
            'index' => $new_index_name,
            'body'  => $schema
        ];
        $this->elasticsearch->indices()->create($idx_params);
        return $new_index_name;
    }

    public function addAlias(string $new_index_name)
    {
        // Remove the old alias if there is one.
        $old_index = $this->getIndexName();
        if ($old_index) {
            $this->elasticsearch->indices()->deleteAlias(['name' => $this->website_index_name, 'index' => $old_index]);
        }

        // Update the website alias to the new index.
        $this->elasticsearch->indices()->putAlias(['index' => $new_index_name, 'name' => $this->website_index_name]);
    }

    public function getIndexName(): string
    {
        try {
            $response = $this->elasticsearch->indices()->getAlias(['name' => $this->website_index_name]);
            $indexes = array_keys($response);
            return $indexes[0] ?? '';
        } catch (\Exception $e) {
            return '';
        }
    }

    public function setIndexName(string $name)
    {
        $this->website_index_name = $name;
    }

    /**
     * Get the date of the last updated record in the index
     *
     * @return \DateTime
     * @throws \Exception
     */
    public function getLastUpdated(): \DateTime
    {
        // Perform an empty search (i.e. show all records) fetching just the first
        // record sorted by last updated date.
        $params = [
            'index' => $this->website_index_name,
            'body'  => [
                'query' => [
                    'match_all' => (object)[]
                ],
                'size'  => '1',
                'sort'  => [
                    [
                        'updated' => 'desc'
                    ]
                ]
            ],
        ];
        $results = $this->elasticsearch->search($params);

        if ($results['hits']['total']['value'] === 0) {
            throw new \Exception("No records found in index {$this->website_index_name}");
        }

        $datetime_string = $results['hits']['hits'][0]['_source']['updated'];
        return new \DateTime($datetime_string);
    }

    /**
     * Create or update a single page
     *
     * @param Page $page
     */
    public function update(Page $page): void
    {
        $guide = $page->getGuide();
        $params = [
            'index' => $this->website_index_name,
            'id'    => $page->getId(),
            'body'  => [
                'title'             => $page->getTitle(),
                'guide_title'       => $guide->title,
                'guide_id'          => $guide->id,
                'text'              => $page->getText(),
                'url'               => $page->getUrl(),
                'guide_url'         => $guide->url,
                'updated'           => $page->getUpdated(),
                'guide_subjects'    => $guide->subjects,
                'guide_tags'        => $guide->tags,
                'guide_description' => $guide->description,
                'canvas'            => $guide->canvas
            ]
        ];
        $this->elasticsearch->index($params);
    }
}