<?php

namespace App\Types;

use TheCodingMachine\GraphQLite\Annotations\SourceField;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * @Type(class="\BCLib\LibKeyClient\LibKeyResponse")
 * @SourceField(name="id")
 * @SourceField(name="type")
 * @SourceField(name="title")
 * @SourceField(name="date")
 * @SourceField(name="authors")
 * @SourceField(name="inPress")
 * @SourceField(name="fullTextFile")
 * @SourceField(name="contentLocation")
 * @SourceField(name="availableThroughBrowzine")
 * @SourceField(name="startPage")
 * @SourceField(name="endPage")
 * @SourceField(name="browzineWebLink")
 * @SourceField(name="journals")
 */
class LibKeyResponse
{
}