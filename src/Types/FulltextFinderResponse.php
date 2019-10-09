<?php

namespace App\Types;

use BCLib\FulltextFinder\FinderResponse;
use TheCodingMachine\GraphQLite\Annotations\SourceField;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * @Type(class=FinderResponse::class)
 * @SourceField(name="crossRefData")
 * @SourceField(name="libKeyData")
 * @SourceField(name="fullText")
 * @SourceField(name="authors")
 * @SourceField(name="volume")
 * @SourceField(name="issue")
 */
class FulltextFinderResponse
{

}