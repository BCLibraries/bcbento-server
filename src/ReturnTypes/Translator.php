<?php

namespace App\ReturnTypes;

use App\Entity\CatalogItem;
use App\Entity\FAQResponse;
use App\Entity\LibrarianRecommendationResponse;
use BCLib\PrimoClient\Doc;
use BCLib\PrimoClient\SearchResponse;

class Translator
{
    public function translateSearchResult(SearchResponse $response): SearchResult
    {
        $docs = array_map(self::translateDoc(...), $response->docs);
        return new SearchResult(
            docs: $docs, search_url: '', didUMean: $response->did_u_mean
        );
    }

    public static function translateDoc(Doc $doc): PrimoDoc|\App\ReturnTypes\Article
    {
        if ($doc->json->adaptor === 'Primo Central') {
            return self::translateArticleResult($doc);
        } else {
            return self::translateCatalogResult($doc);
        }
    }

    public static function translateCatalogResult(Doc $primo_doc): PrimoDoc
    {
        return new PrimoDoc(
            type: $primo_doc->type,
            id: $primo_doc->id,
            linkableId: $primo_doc->id,
            title: $primo_doc->title,
            creator: $primo_doc->creator ? $primo_doc->creator : '',
            mms: $primo_doc->pnx('display', 'mms')[0],
            contributors: $primo_doc->contributors,
            publisher: $primo_doc->publisher,
            date: $primo_doc->date,
            findingAidUrl: '',
            isPhysical: $primo_doc->is_physical,
            isElectronic: $primo_doc->is_electronic,
            coverImages: array_map(fn($image) => $image->getUrl(), $primo_doc->cover_images),
            screenCap: '',
            holdings: array_map(self::translateHolding(...), $primo_doc->holdings),
            availability: self::translateAvailability($primo_doc->getAvailability())
        );
    }

    public static function translateArticleResult($primo_doc): \App\ReturnTypes\Article
    {
        return new \App\ReturnTypes\Article(
            type: $primo_doc->type,
            id: $primo_doc->id,
            linkableId: '',
            title: $primo_doc->title,
            creator: $primo_doc->creator ? $primo_doc->creator : '',
            journal_title: $primo_doc->journal_title[0] ?? '',
            publisher: $primo_doc->publisher,
            date: $primo_doc->date,
            ispartof: $primo_doc->json->pnx->display->ispartof[0] ?? ''
        );
    }

    public static function translateHolding(\BCLib\PrimoClient\Holding $holding): Holding
    {
        return new Holding(
            availabilityStatus: $holding->availability_status,
            libraryCode: $holding->library_code,
            libraryDisplay: $holding->getLibraryDisplay(),
            locationDisplay: $holding->location_display,
            callNumber: $holding->call_number
        );
    }

    public static function translateAvailability(?\App\Service\LoanMonitor\Availability $availability): ?Availability
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

    public function translateLibrariansResult(LibrarianRecommendationResponse $response): SearchResult
    {
        $docs = array_map(self::translateLibrarian(...), $response->getDocs());
        return new SearchResult(
            docs: $docs, search_url: ''
        );
    }

    public function translateFAQResults(FAQResponse $response): SearchResult
    {
        $docs = array_map(self::translateFAQResult(...), $response->getResults());
        return new SearchResult(
            docs: $docs, search_url: ''
        );
    }

    public static function translateLibrarian(\App\Entity\Librarian $librarian): Librarian
    {
        return new Librarian($librarian->getId(),
            $librarian->getName(),
            $librarian->getEmail(),
            $librarian->getImage(),
            $librarian->getScore(),
            $librarian->getSubjects()
        );
    }

    public static function translateFAQResult(\App\Entity\FAQResult $result ): FAQResult
    {
        return new FAQResult(
            $result->getId(),
            $result->getQuestion(),
            $result->getUrl(),
            $result->getTopics()
        );
    }
}
