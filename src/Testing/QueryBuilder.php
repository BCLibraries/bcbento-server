<?php

namespace App\Testing;

use GraphQL\Query;

class QueryBuilder
{

    public static function buildCatalogQuery(string $search_terms, int $limit = 3): Query
    {
        $docs_query = self::docsQuery();
        return (new Query('searchCatalog'))
            ->setArguments(
                [
                    'keyword' => $search_terms,
                    'limit'   => $limit
                ]
            )
            ->setSelectionSet([
                'searchUrl',
                'didUMean',
                'total',
                $docs_query
            ]);
    }

    private static function docsQuery(): Query
    {
        $cover_images = self::coverImagesQuery();
        $availabilities = self::availabilityQuery();
        $link_to_finding_aid = self::linkToFindingAidQuery();

        return (new Query('docs'))
            ->setSelectionSet(
                [
                    'id',
                    'title',
                    'date',
                    'type',
                    'creator',
                    'contributors',
                    'linkableId',
                    'available',
                    'hathitrustUrl',
                    'isPhysical',
                    'isElectronic',
                    'screenCap',
                    'mms',
                    $cover_images,
                    $availabilities,
                    $link_to_finding_aid
                ]
            );
    }

    private static function coverImagesQuery(): Query
    {
        return (new Query('coverImages'))
            ->setSelectionSet(
                [
                    'url'
                ]
            );
    }

    private static function availabilityQuery(): Query
    {
        return (new Query('availability'))
            ->setSelectionSet(
                [
                    'libraryName',
                    'locationName',
                    'callNumber',
                    'totalCount',
                    'otherAvailabilities'
                ]
            );
    }

    private static function linkToFindingAidQuery(): Query
    {
        return (new Query('linkToFindingAid'))
            ->setSelectionSet([
                'url'
            ]);
    }
}