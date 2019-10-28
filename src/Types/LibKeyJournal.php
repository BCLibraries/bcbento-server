<?php

namespace App\Types;

use TheCodingMachine\GraphQLite\Annotations\SourceField;
use TheCodingMachine\GraphQLite\Annotations\Type;


/**
 * @Type(class="\BCLib\LibKeyClient\Journal")
 * @SourceField(name="id")
 * @SourceField(name="type")
 * @SourceField(name="title")
 * @SourceField(name="issn")
 * @SourceField(name="SJRValue")
 * @SourceField(name="coverImageUrl")
 * @SourceField(name="browzineEnabled")
 * @SourceField(name="browzineWebLink")
 */
class LibKeyJournal
{
}