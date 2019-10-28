<?php

namespace App\Service;

use App\Entity\CatalogItem;
use BCLib\LibKeyClient\LibKeyClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class LibKeyService
{
    /** @var LibKeyClient */
    private $client;

    /** @var ResponseInterface[] */
    private $responses;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(LibKeyClient $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * @param CatalogItem[] $docs
     */
    public function addLibKeyAvailability(array $docs): void
    {
        $start_time = microtime(true);

        foreach ($docs as $doc) {
            $this->lookup($doc);
        }

        foreach ($docs as $doc) {
            try {
                $this->processResponse($doc);
            } catch (TransportExceptionInterface $e) {
                // If a transport error occurs, move on.
            }
        }

        $duration = microtime(true) - $start_time;
        $this->logger->info("LibKey added time: {$duration} seconds");
    }

    /**
     * @param CatalogItem $doc
     */
    private function lookup(CatalogItem $doc): void
    {
        if (!$doi = $doc->getDOI()) {
            return;
        }
        try {
            $this->responses[$doi] = $this->client->request($doi);
        } catch (ClientException $e) {
            // Client exceptions are usually 404s returned by LibKey because they do not have a DOI.
        }
    }

    /**
     * @param CatalogItem $doc
     * @throws TransportExceptionInterface
     * @throws ClientException
     */
    private function processResponse(CatalogItem $doc): void
    {
        $doi = $doc->getDOI();

        if (!$doi || !isset($this->responses[$doi]) || $this->responses[$doi]->getStatusCode() !== 200) {
            return;
        }

        $parsed = $this->client->parse($this->responses[$doi]);
        $doc->setLibkeyAvailability($parsed);
    }

}