<?php

namespace App\Indexer\Website;

class Guide
{
    public $id;
    public $title;
    public $url;
    public $tags = [];
    public $subjects = [];
    public $description;
    public $canvas = [];

    /**
     * @var Page[]
     */
    public $pages = [];

    public function updatedSince(\DateTime $since): bool
    {
        foreach ($this->pages as $page) {
            if ($page->updatedSince($since)) {
                return true;
            }
        }
        return false;
    }
}