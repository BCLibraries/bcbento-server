<?php

namespace App\Testing;

use GraphQL\Query;

class QueryBuilder
{

    public static function buildCatalogQuery(string $search_terms): string
    {
        $docs_query = self::docsQuery();
        return <<<GRAPHQL
            {
              searchCatalog( keyword: "$search_terms", limit: 3) {   
                searchUrl
                didUMean,
                total,
                $docs_query
              }
            }
GRAPHQL;
    }

    private static function docsQuery(): string
    {
        $cover_images = self::coverImagesQuery();
        $availabilities = self::availabilityQuery();
        $link_to_finding_aid = self::linkToFindingAidQuery();
        return <<<GRAPHQL
            docs {
                id,
                title,
                date,
                type,
                creator,
                contributors,
                linkableId,
                available,
                hathitrustUrl,
                isPhysical,
                isElectronic,
                screenCap,
                mms,
                $cover_images,
                $availabilities,
                $link_to_finding_aid
            }
GRAPHQL;
    }

    private static function coverImagesQuery(): string
    {
        return <<<GRAPHQL
            coverImages {
                url
            }
GRAPHQL;
    }

    private static function availabilityQuery(): string
    {
        return <<<GRAPHQL
            availability {
                libraryName,
                locationName,
                callNumber,
                totalCount,
                otherAvailabilities
            }
GRAPHQL;
    }

    private static function linkToFindingAidQuery(): string
    {
        return <<<GRAPHQL
            linkToFindingAid {
                url
            }
GRAPHQL;
    }
}