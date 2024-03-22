<?php

namespace App\ReturnTypes;

use App\Entity\CatalogItem;
use BCLib\PrimoClient\Doc;
use BCLib\PrimoClient\SearchResponse;

class Translator
{
    public function translatePrimoDoc(CatalogItem $primo_doc): PrimoDoc
    {
        return new PrimoDoc(
            type: $primo_doc->type,
            id: $primo_doc->id,
            linkableId: $primo_doc->id,
            title: $primo_doc->title,
            creator: $primo_doc->creator,
            mms: $primo_doc->pnx('search', 'addsrcrecordid')[0],
            contributors: $primo_doc->contributors,
            publisher: $primo_doc->publisher,
            date: $primo_doc->date,
            findingAidUrl: '',
            isPhysical: $primo_doc->is_physical,
            isElectronic: $primo_doc->is_electronic,
            coverImages: array_map(fn($image) => $image->getUrl(), $primo_doc->cover_images),
            screenCap: '',
            holdings: array_map([$this, 'translateHolding'], $primo_doc->holdings),
            availability: $this->translateAvailability($primo_doc->getAvailability())
        );
    }

    public function translateSearchResult(SearchResponse $response): SearchResult
    {
        $docs = [];
        foreach ($response->docs as $doc) {
            $docs[] = $this->translatePrimoDoc($doc);
        }
        return new SearchResult(
            docs: $docs, search_url: '', didUMean: $response->did_u_mean
        );
    }

    public function translateHolding(\BCLib\PrimoClient\Holding $holding): Holding
    {
        return new Holding(
            availabilityStatus: $holding->availability_status,
            libraryCode: $holding->library_code,
            libraryDisplay: $holding->getLibraryDisplay(),
            locationDisplay: $holding->location_display,
            callNumber: $holding->call_number
        );
    }

    public function translateAvailability(?\App\Service\LoanMonitor\Availability $availability): ?Availability
    {
        if (is_null($availability)) {
            return null;
        }
        return new Availability(
            libraryName: $availability->getLibraryName(),
            locationName: $availability->getLocationName(),
            totalCount: $availability->getTotalCount(),
            callNumber: $availability->getCallNumber(),
            otherAvailabilities: $availability->getOtherAvailabilities()
        );
    }
}
