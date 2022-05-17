<?php

namespace App\Indexer\Website;

use Cassandra\Date;
use http\Exception;

class Page
{
    private string $id;
    private string $title;
    private \DateTime $updated;
    private string $text;
    private string $url;
    private Guide $guide;

    public function __construct(string $id, string $title, string $updated, string $url, Guide $guide)
    {
        $this->id = $id;
        $this->title = $title;
        $this->updated = new \DateTime($updated);
        $this->url = $url;
        $this->guide = $guide;
        $this->text = '';
    }

    public function setText(string $text): void
    {
        if (strlen($this->text) > 0) {
            throw new \Exception("Page text for {$this->guide->title}:{$this->title} has already been set");
        }

        $this->text = $text;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getUpdated(): string
    {
        return $this->updated->format('Y-m-d\TH:i:s');
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getGuide(): Guide
    {
        return $this->guide;
    }

    public function updatedSince(\DateTime $since): bool
    {
        return $this->updated > $since;
    }
}