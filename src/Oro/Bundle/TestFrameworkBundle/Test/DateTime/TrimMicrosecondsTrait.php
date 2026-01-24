<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DateTime;

/**
 * Provides functionality to trim microseconds from {@see \DateTime} objects for database comparison.
 *
 * This trait helps normalize {@see \DateTime} objects to match database datetime format (without microseconds),
 * which is useful for comparing dynamically created DateTime objects with database values in tests.
 */
trait TrimMicrosecondsTrait
{
    /**
     * DateTime object starting from PHP 7.1 contains microseconds,
     * but database doesn't keep microsends in datetime fields.
     * This method allow us convert dynamic DateTime object to database format.
     *
     * @param $dateTimeObj
     *
     * @return \DateTime
     */
    protected function trimMicrosecondsFromDateTimeObject($dateTimeObj)
    {
        if (! $dateTimeObj instanceof \DateTime) {
            return $dateTimeObj;
        }

        return new \DateTime($dateTimeObj->format('Y-m-d H:i:s'), new \DateTimeZone('UTC'));
    }
}
