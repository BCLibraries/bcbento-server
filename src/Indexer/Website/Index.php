<?php

namespace App\Indexer\Website;

use Elastic\Elasticsearch\Client;
use PHPUnit\Exception;

class Index extends \App\Indexer\Index
{
    public const ALIAS = 'website';
    public const SCHEMA = 'website-schema.json';

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
            'index' => $this->index_name,
            'body' => [
                'query' => [
                    'match_all' => (object)[]
                ],
                'size' => '1',
                'sort' => [
                    [
                        'updated' => 'desc'
                    ]
                ]
            ],
        ];
        $results = $this->elasticsearch->search($params);

        if ($results['hits']['total']['value'] === 0) {
            throw new \Exception("No records found in index {$this->index_name}");
        }

    }

    /**
     * Create or update a single page
     *
     * @param Page $single_item
     */
    public function update($single_item): void
    {
        $guide = $single_item->getGuide();
        $params = [
            'index' => $this->index_name,
            'id' => $single_item->getId(),
            'body' => [
                'title' => $single_item->getTitle(),
                'guide_title' => $guide->title,
                'guide_id' => $guide->id,
                'text' => $single_item->getText(),
                'url' => $single_item->getUrl(),
                'guide_url' => $guide->url,
                'updated' => $single_item->getUpdated(),
                'guide_subjects' => $guide->subjects,
                'guide_tags' => $guide->tags,
                'guide_description' => $guide->description,
                'canvas' => $guide->canvas
            ]
        ];
        $this->elasticsearch->index($params);
    }
}
