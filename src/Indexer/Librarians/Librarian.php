<?php

namespace App\Indexer\Librarians;

use Elastic\Elasticsearch\Response\Elasticsearch;

class Librarian
{
    private string $id;
    private ?string $email;
    private string $first_name;
    private string $last_name;
    private ?string $image;
    private ?string $title;

    /** @var string[] */
    private array $subjects = [];

    /** @var string[] */
    private array $taxonomy = [];

    /** @var string[] */
    private array $terms = [];

    /**
     * @param string $id
     * @param ?string $email
     * @param string $first_name
     * @param string $last_name
     * @param ?string $image
     * @param ?string $title
     * @param string[] $subjects
     * @param string[] $taxonomy
     * @param string[] $terms
     */
    public function __construct(string $id, ?string $email, string $first_name, string $last_name, ?string $image, ?string $title, ?array $subjects, ?array $taxonomy, ?array $terms)
    {
        $this->id = $id;
        $this->email = $email;
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->image = $image;
        $this->title = $title;
        $this->subjects = $subjects ?? [];
        $this->taxonomy = $taxonomy ?? [];
        $this->terms = $terms ?? [];
    }

    /**
     * Build a librarian from a Librarian object in ElasticSearch
     *
     * @param Elasticsearch $response an ElasticSearch hit
     * @return Librarian
     */
    public static function buildFromElasticSearch(Elasticsearch $response): Librarian
    {


        return new Librarian(
            $response['_id'],
            $response['_source']['email'] ?? null,
            $response['_source']['first_name'],
            $response['_source']['last_name'],
            $response['_source']['image'] ?? null,
            $response['_source']['title'] ?? null,
            $response['_source']['subjects'],
            $response['_source']['taxonomy'],
            $response['_source']['terms']
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getFirstName(): string
    {
        return $this->first_name;
    }

    public function getLastName(): string
    {
        return $this->last_name;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getSubjects(): array
    {
        return $this->subjects;
    }

    public function getTaxonomy(): array
    {
        return $this->taxonomy;
    }

    public function getTerms(): array
    {
        return $this->terms;
    }

}
