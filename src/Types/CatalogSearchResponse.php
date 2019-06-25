<?php

namespace App\Types;

use BCLib\PrimoClient\SearchResponse;
use TheCodingMachine\GraphQLite\Annotations\SourceField;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * @Type(class=SearchResponse::class)
 * @SourceField(name="docs")
 * @SourceField(name="total")
 * @SourceField(name="first")
 * @SourceField(name="last")
 * @SourceField(name="didUMean")
 * @SourceField(name="controlledVocabulary")
 * @SourceField(name="facets")
 */
class CatalogSearchResponse
{
}