<?php

namespace App\Indexer\Librarians;

class Index extends \App\Indexer\Index
{
    public const ALIAS = 'librarians';
    public const SCHEMA = 'librarians-schema.json';

    /**
     * @param Librarian $single_item
     * @return void
     */
    public function update($single_item): void
    {
        $params = [
            'index' => $this->index_name,
            'id'    => $single_item->getId(),
            'body'  => [
                'email'      => $single_item->getEmail(),
                'first_name' => $single_item->getFirstName(),
                'last_name'  => $single_item->getLastName(),
                'image'      => $single_item->getImage(),
                'title'      => $single_item->getTitle(),
                'subjects'   => $single_item->getSubjects(),
                'taxonomy'   => $single_item->getTaxonomy(),
                'terms'      => $single_item->getTerms(),
                'library'    => '',
                'updated'    => (new \DateTime())->format('Y-m-d\TH:i:s') // Assume this librarian has just been updated.
            ]
        ];
        $this->elasticsearch->index($params);
    }

    /**
     * Lookup a librarian by ID
     */
    public function getLibrarian(string $id): ?Librarian
    {
        $params = [
            'index' => $this->index_name,
            'id'    => $id
        ];
        $result = $this->elasticsearch->get($params);
        return ($result['found'] > 0) ? Librarian::buildFromElasticSearch($result) : null;
    }

    public function getAll(): \Generator
    {
        $params = [
            'index' => self::ALIAS,
            'from' => 0,
            'size' => 1000
        ];
        $response = $this->elasticsearch->search($params);
        foreach ($response['hits']['hits'] as $hit) {
            yield Librarian::buildFromElasticSearch($hit);
        }
    }

    public function delete(Librarian $librarian)
    {
        $params = [
            'index' => $this->index_name,
            'id'    => $librarian->getId()
        ];
        $this->elasticsearch->delete($params);
    }
}