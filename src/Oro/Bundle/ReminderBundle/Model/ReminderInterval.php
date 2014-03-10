<?php

namespace Oro\Bundle\ReminderBundle\Model;

/**
 * Represents remind interval, a pair of number and unit of interval.
 */
class ReminderInterval
{
    const UNIT_MINUTE = 'M';
    const UNIT_HOUR = 'H';
    const UNIT_DAY = 'D';
    const UNIT_WEEK = 'W';

    /**
     * Creates DateInterval that match reminder interval params
     *
     * @param int $number
     * @param string $unit
     * @return \DateInterval
     */
    public static function createDateInterval($number, $unit)
    {
        if ($unit == self::UNIT_DAY || $unit == self::UNIT_HOUR) {
            $format = 'T%d%s';
        } else {
            $format = 'P%d%s';
        }

        return new \DateInterval(sprintf($format, $number, $unit));
    }
}
