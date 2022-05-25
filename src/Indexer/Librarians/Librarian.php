<?php

namespace App\Indexer\Librarians;

class Librarian
{
    private string $id;
    private string $email;
    private string $first_name;
    private string $last_name;
    private string $image;
    private string $title;

    /** @var string[] */
    private array $subjects;

    /** @var string[] */
    private array $taxonomy;

    /** @var string[] */
    private array $terms;

    /**
     * @param string $id
     * @param string $email
     * @param string $first_name
     * @param string $last_name
     * @param string $image
     * @param string $title
     * @param string[] $subjects
     * @param string[] $taxonomy
     * @param string[] $terms
     */
    public function __construct(string $id, string $email, string $first_name, string $last_name, string $image, string $title, array $subjects, array $taxonomy, array $terms)
    {
        $this->email = $email;
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->image = $image;
        $this->title = $title;
        $this->subjects = $subjects;
        $this->taxonomy = $taxonomy;
        $this->terms = $terms;
        $this->id = $id;
    }

    /**
     * Build a librarian from a Librarian object in ElasticSearch
     *
     * @param \stdClass $json an ElasticSearch hit
     * @return Librarian
     */
    public static function buildFromElasticSearch(\stdClass $json): Librarian
    {
        return new Librarian(
            $json->id,
            $json->_source->email,
            $json->_source->first_name,
            $json->_source->last_name,
            $json->_source->image,
            $json->_source->title,
            $json->_source->subjects,
            $json->_source->taxonomy,
            $json->_source->terms
        );
    }
}