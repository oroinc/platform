<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DateTime;

trait TrimMicrosecondsTrait
{
    /**
     * DateTime object starting from PHP 7.1 contains microseconds,
     * but database doesn't keep microsends in datetime fields.
     * This method allow us convert dynamic DateTime object to database format.
     *
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
