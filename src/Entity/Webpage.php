<?php

namespace App\Entity;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * @Type()
 */
class Webpage
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $guide_title;

    /**
     * @var string
     */
    private $guide_url;

    /**
     * @var string[]
     */
    private $highlight;

    /**
     * @var string
     */
    private $updated;

    public function __construct(
        string $url,
        string $title,
        string $guide_title,
        string $guide_url,
        array $highlight,
        string $updated
    ) {
        $this->url = $url;
        $this->title = $title;
        $this->guide_title = $guide_title;
        $this->guide_url = $guide_url;
        $this->highlight = $highlight;
        $this->updated = $updated;
    }

    /**
     * @Field()
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @Field()
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @Field()
     */
    public function getGuideTitle(): string
    {
        return $this->guide_title;
    }

    /**
     * @Field()
     */
    public function getGuideUrl(): string
    {
        return $this->guide_url;
    }

    /**
     * @return string[]
     * @Field()
     */
    public function getHighlight(): array
    {
        return $this->highlight;
    }

    /**
     * @Field()
     */
    public function getUpdated(): string
    {
        return $this->updated;
    }

    /**
     * @Field()
     */
    public function getFullTitle(): string
    {
        return $this->title === 'Home' ? $this->guide_title : "{$this->guide_title} : {$this->title}";
    }
}