<?php

namespace App\Entity;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * @Type()
 */
class Librarian
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $image;

    /**
     * @var float
     */
    private $score;

    /**
     * @var string[]
     */
    private $subjects;

    public function __construct(string $id, string $name, string $email, string $image, float $score, array $subjects)
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->image = $image;
        $this->score = $score;
        $this->subjects = $subjects;
    }

    /**
     * @Field()
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @Field()
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @Field()
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @Field()
     */
    public function getImage(): string
    {
        return $this->image;
    }

    /**
     * @Field()
     */
    public function getScore(): float
    {
        return $this->score;
    }

    /**
     * @return string[]
     * @Field()
     */
    public function getSubjects(): array
    {
        return $this->subjects;
    }
}