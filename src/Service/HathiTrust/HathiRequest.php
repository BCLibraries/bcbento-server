<?php

namespace App\Service\HathiTrust;

use App\Entity\CatalogItem;

class HathiRequest
{
    private const BIB_API_BASE_URL = 'https://catalog.hathitrust.org/api/volumes/brief/json/';

    /** @var CatalogItem[] */
    private array $docs = [];

    /**
     * @param CatalogItem[] $docs
     */
    public function __construct(array $docs = [])
    {
        foreach ($docs as $doc) {
            $this->addDoc($doc);
        }
    }

    /**
     * Add a document to the request
     *
     * Electronic documents and documents without useful identifiers will not be added.
     *
     * @param CatalogItem $doc
     */
    public function addDoc(CatalogItem $doc): void
    {
        $id = $this->getBestId($doc);

        if ($doc->is_physical && isset($id)) {
            $this->docs[$id] = $doc;
        }
    }

    /**
     * @return string
     */
    public function getURL(): string
    {
        return self::BIB_API_BASE_URL . implode('|', array_keys($this->docs));
    }

    public function updateDoc(string $id, string $hathi_url): void
    {
        if (isset($this->docs[$id])) {
            $this->docs[$id]->setHathitrustUrl($hathi_url);
        }
    }

    /**
     * @param CatalogItem $doc
     * @return string|null
     */
    private function getBestId(CatalogItem $doc): ?string
    {
        $id = null;
        if (isset($doc->oclcid[0])) {
            $id = "oclc:{$doc->oclcid[0]}";
        }
        return $id;
    }
}