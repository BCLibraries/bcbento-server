<?php

namespace App\Types;

use BCLib\PrimoClient\Link;
use TheCodingMachine\GraphQLite\Annotations\SourceField;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * @Type(class=Link::class)
 * @SourceField(name="label")
 * @SourceField(name="url")
 * @SourceField(name="type")
 */
class BibLink
{
}