<?php

namespace App\Types;

use BCLib\PrimoClient\Holding;
use TheCodingMachine\GraphQLite\Annotations\SourceField;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * @Type(class=Holding::class)
 * @SourceField(name="ilsId")
 * @SourceField(name="libraryCode")
 * @SourceField(name="libraryDisplay")
 * @SourceField(name="locationCode")
 * @SourceField(name="locationDisplay")
 * @SourceField(name="callNumber")
 * @SourceField(name="availabilityStatus")
 * @SourceField(name="items")
 */
class HoldingRecord
{
}