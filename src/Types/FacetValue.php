<?php

namespace App\Types;

use BCLib\PrimoClient\ResponseFacetValue;
use TheCodingMachine\GraphQLite\Annotations\SourceField;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * @Type(class=ResponseFacetValue::class)
 * @SourceField(name="value")
 * @SourceField(name="count")
 */
class FacetValue
{
}