<?php

namespace App\Types;

use App\Service\LoanMonitor\Availability;
use TheCodingMachine\GraphQLite\Annotations\SourceField;
use TheCodingMachine\GraphQLite\Annotations\Type;


/**
 * @Type(class=Availability::class)
 * @SourceField(name="availableCount")
 * @SourceField(name="totalCount")
 * @SourceField(name="callNumber")
 * @SourceField(name="libraryCode")
 * @SourceField(name="locationCode")
 * @SourceField(name="libraryName")
 * @SourceField(name="locationName")
 * @SourceField(name="otherAvailabilities")
 */
class AvailabilityRecord
{
}