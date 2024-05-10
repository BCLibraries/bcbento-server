<?php

namespace App\Entity;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * @Type()
 */
readonly class FAQResult
{
    /** @Field */
    public int $id;

    /** @Field */
    public string $question;

    /** @Field */
    public string $url;

    /**
     * @Field
     * @var string[]
     */
    public array $topics;

    public function __construct(int $id, string $question, string $url, array $topics = [])
    {
        $this->id = $id;
        $this->question = $question;
        $this->url = $url;
        $this->topics = $topics;
    }
}
