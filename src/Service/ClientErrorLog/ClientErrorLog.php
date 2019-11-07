<?php

namespace App\Service\ClientErrorLog;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Handles logging client-side errors
 *
 * @package App\Service\ClientErrorLog
 */
class ClientErrorLog
{
    /**
     * Valid PSR-3 log levels
     */
    public const LEVELS = [
        LogLevel::EMERGENCY,
        LogLevel::ALERT,
        LogLevel::CRITICAL,
        LogLevel::ERROR,
        LogLevel::WARNING,
        LogLevel::INFO,
        LogLevel::DEBUG
    ];

    /**
     * @var LoggerInterface
     */
    private $log;

    public function __construct(LoggerInterface $log)
    {
        $this->log = $log;
    }

    /**
     * Add logged events
     *
     * @param string $json_request A JSON string from the body of a logging request
     * @param string $ip The IP address
     */
    public function add(string $json_request, string $ip): void
    {
        $request = json_decode($json_request, false);

        // Nothing valid to log? Log an error!
        if (!isset($request->events)) {
            $this->addLogEntry(LogLevel::ERROR, "Client log request has no entries: $json_request", $ip);
            return;
        }

        // Log entries may be sent in a batch for efficient logging.
        foreach ($request->events as $event) {
            $entry = $this->buildEntry($event);
            $message = "[$ip] {$entry->getMessage()}";
            $this->addLogEntry($entry->getLevel(), $message, $ip);
        }
    }

    /**
     * Build a single entry
     *
     * @param \stdClass $request_entry
     * @return Entry
     */
    private function buildEntry(\stdClass $request_entry): Entry
    {
        // No message? Log the whole entry.
        $message = $request_entry->message ?? "Invalid client log entry: $request_entry";

        // Invalid levels should be errors, but take note.
        if (!in_array($request_entry->level, self::LEVELS, true)) {
            $level = LogLevel::ERROR;
            $message = "[Invalid Log Level ({$request_entry->level})] {$message}";
        } else {
            $level = $request_entry->level;
        }

        return new Entry($level, $message);
    }

    private function addLogEntry(string $level, string $message, string $ip): void
    {
        $this->log->log($level, $message, ['ip' => $ip]);
    }
}