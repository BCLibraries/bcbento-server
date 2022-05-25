<?php

namespace App\Indexer;

use Elasticsearch\Client;

abstract class Index
{
    public const ALIAS = null;
    public const SCHEMA = null;

    protected Client $elasticsearch;
    protected ?string $index_name;

    /**
     * @param Client $elasticsearch
     * @param string|null $index_name
     * @throws IndexerException
     */
    public function __construct(Client $elasticsearch, ?string $index_name = null)
    {
        // Make sure the child classes set the constants.
        if (static::SCHEMA === null || static::ALIAS === null) {
            throw new IndexerException("Index subclasses must define SCHEMA and ALIAS");
        }

        $this->elasticsearch = $elasticsearch;

        // If the caller has specified an index name, use it. Otherwise, just use the alias.
        $this->index_name = $index_name ?: static::ALIAS;
    }

    public function create(): string
    {
        $this->index_name = static::ALIAS . '_' . time();
        $child_class_filepath = dirname((new \ReflectionClass(static::class))->getFileName());
        $schema_json = file_get_contents("$child_class_filepath/" . static::SCHEMA);
        $schema = json_decode($schema_json, true);
        $idx_params = [
            'index' => $this->index_name,
            'body'  => $schema
        ];
        $this->elasticsearch->indices()->create($idx_params);

        return $this->index_name;
    }

    public function addAlias(string $new_index_name)
    {
        // Remove the old alias if there is one.
        $old_index = $this->getAliasedIndex();
        if ($old_index) {
            $this->elasticsearch->indices()->deleteAlias(['name' => static::ALIAS, 'index' => $old_index]);
        }

        // Update the website alias to the new index.
        $this->elasticsearch->indices()->putAlias(['index' => $new_index_name, 'name' => static::ALIAS]);
    }

    protected function getAliasedIndex(): string
    {
        try {
            $response = $this->elasticsearch->indices()->getAlias(['name' => static::ALIAS]);
            $indexes = array_keys($response);
            return $indexes[0] ?? '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Update a single item
     *
     * @param $single_item
     * @return void
     */
    abstract public function update($single_item): void;
}