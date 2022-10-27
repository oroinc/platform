<?php

namespace Oro\Bundle\ReportBundle\Tests\Functional;

/**
 * Simple functions for creating DateTime objects and it formatting to generate reports.
 */
trait ReportDateTimeFilterExtension
{
    public function dateTimeWithModify(
        string $modify = '+0 seconds',
        string $formattedTime = 'now',
        string $timeZone = 'UTC'
    ): \DateTime {
        if (false === strtotime($formattedTime)) {
            throw new \LogicException(sprintf('The time "%s" is not valid.', $formattedTime));
        }

        $timeZone = $this->getTimeZone($timeZone);
        $dateTime = new \DateTime('now', $timeZone);
        $modifiedDateTime = $dateTime->modify($modify);
        if (false === $modifiedDateTime) {
            throw new \LogicException(sprintf('Modifier "%s" is not valid.', $modify));
        }

        return $modifiedDateTime;
    }

    public function dateTimeWithModifyAsString(
        string $modify = '+0 day',
        string $formattedTime = 'now', //'now' or 2021-12-12 12:12
        string $timeZone = 'UTC',
        string $format = 'Y-m-d H:i:s'
    ): string {
        return $this->dateTimeWithModify($modify, $formattedTime, $timeZone)->format($format);
    }

    private function getTimeZone(string $timeZone = 'UTC'): \DateTimeZone
    {
        $identifiers = array_values(timezone_identifiers_list());
        if (!in_array($timeZone, $identifiers)) {
            throw new \LogicException(sprintf('The timezone "%s" not valid.', $timeZone));
        }

        return new \DateTimeZone($timeZone);
    }
}
