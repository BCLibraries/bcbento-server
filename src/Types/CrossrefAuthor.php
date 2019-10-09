<?php

namespace App\Types;

use \BCLib\FulltextFinder\Crossref\Author;
use TheCodingMachine\GraphQLite\Annotations\SourceField;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * @Type(class=Author::class)
 * @SourceField(name="givenName")
 * @SourceField(name="familyName")
 * @SourceField(name="sequence")
 * @SourceField(name="orcid")
 * @SourceField(name="authenticatedOrcid")
 * @SourceField(name="affiliation")
 */
class CrossrefAuthor
{
}