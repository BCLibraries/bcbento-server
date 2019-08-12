<?php

namespace App\Types;

use BCLib\PrimoClient\Item;
use TheCodingMachine\GraphQLite\Annotations\SourceField;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * @Type(class=Item::class)
 * @SourceField(name="holdingId")
 * @sourceField(name="institution")
 * @sourceField(name="library")
 * @sourceField(name="libraryDisplay")
 * @sourceField(name="location")
 * @sourceField(name="locationCode")
 * @sourceField(name="callNumber")
 * @sourceField(name="availability")
 * @sourceField(name="number")
 * @sourceField(name="numberUnavailable")
 * @sourceField(name="multiVolume")
 * @sourceField(name="numberLoans")
 */
class ItemRecord
{
}