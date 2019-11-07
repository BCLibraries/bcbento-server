<?php

namespace App\Service\ClientErrorLog;

/**
 * A client-side error log entry
 *
 * @package App\Service\ClientErrorLog
 */
class Entry
{
    /** @var string */
    private $message;

    /** @var string */
    private $level;

    public function __construct(string $level, string $message)
    {
        $this->level = $level;
        $this->message = $message;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getLevel(): string
    {
        return $this->level;
    }
}