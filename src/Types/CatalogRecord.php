<?php

namespace App\Types;

use BCLib\PrimoClient\Doc;
use TheCodingMachine\GraphQLite\Annotations\SourceField;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * @Type(class=Doc::class)
 * @SourceField(name="title")
 * @SourceField(name="creator")
 * @SourceField(name="contributors")
 * @SourceField(name="date")
 * @SourceField(name="publisher")
 * @SourceField(name="holdings")
 * @SourceField(name="isbn")
 * @SourceField(name="issn")
 * @SourceField(name="oclcid")
 * @SourceField(name="displaySubject")
 * @SourceField(name="genres")
 * @SourceField(name="creatorFacet")
 * @SourceField(name="collectionFacet")
 * @SourceField(name="resourcetypeFacet")
 * @SourceField(name="languages")
 * @SourceField(name="format")
 * @SourceField(name="description")
 * @SourceField(name="frbrGroupId")
 * @SourceField(name="coverImages")
 * @SourceField(name="openurl")
 * @SourceField(name="openurlFulltext")
 * @sourceField(name="sortTitle")
 * @sourceField(name="sortCreator")
 * @sourceField(name="sortDate")
 * @sourceField(name="isElectronic")
 * @sourceField(name="isDigital")
 * @sourceField(name="isPhysical")
 */
class CatalogRecord
{
}