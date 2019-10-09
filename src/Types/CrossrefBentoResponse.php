<?php


namespace App\Types;

use TheCodingMachine\GraphQLite\Annotations\SourceField;
use TheCodingMachine\GraphQLite\Annotations\Type;
/**
 * @Type(class="\BCLib\FulltextFinder\Crossref\CrossrefResponse")
 * @SourceField(name="DOI")
 * @SourceField(name="titles")
 * @SourceField(name="subtitles")
 * @SourceField(name="shortTitles")
 * @SourceField(name="authors")
 * @SourceField(name="type")
 * @SourceField(name="containerTitles")
 * @SourceField(name="shortContainerTitles")
 * @SourceField(name="publisher")
 * @SourceField(name="volume")
 * @SourceField(name="issue")
 * @SourceField(name="page")
 * @SourceField(name="score")
 * @SourceField(name="publishedPrintDate")
 * @SourceField(name="publishedOnlineDate")
 * @SourceField(name="alternativeIds")
 * @SourceField(name="referenceCount")
 * @SourceField(name="isReferencedByCount")
 */
class CrossrefBentoResponse
{
}