<?php

namespace App\Types;

use BCLib\PrimoClient\ResponseFacet;
use TheCodingMachine\GraphQLite\Annotations\SourceField;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * @Type(class=ResponseFacet::class)
 * @SourceField(name="name")
 * @SourceField(name="values")
 */
class Facet
{
}