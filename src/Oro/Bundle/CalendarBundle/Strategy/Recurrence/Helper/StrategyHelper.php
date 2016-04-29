<?php

namespace Oro\Bundle\CalendarBundle\Strategy\Recurrence\Helper;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;

class StrategyHelper
{
    /** @var array */
    protected static $instanceRelativeValues = [
        Recurrence::INSTANCE_FIRST => 'first',
        Recurrence::INSTANCE_SECOND => 'second',
        Recurrence::INSTANCE_THIRD => 'third',
        Recurrence::INSTANCE_FOURTH => 'fourth',
        Recurrence::INSTANCE_LAST => 'last',
    ];

    /** @var array */
    protected static $weekdays = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
    ];

    /** @var array */
    protected static $weekends = [
        'saturday',
        'sunday',
    ];

    /**
     * Returns recurrence instance relative value by its key.
     *
     * @param $key
     *
     * @return null|string
     */
    public function getInstanceRelativeValue($key)
    {
        return empty(self::$instanceRelativeValues[$key]) ? null : self::$instanceRelativeValues[$key];
    }

    /**
     * @param Recurrence $recurrence
     *
     * @throws \RuntimeException
     */
    public function validateRecurrence(Recurrence $recurrence)
    {
        if (false === filter_var($recurrence->getInterval(), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
            throw new \RuntimeException('Interval should be an integer with min_rage >= 1.');
        }
        if (! $recurrence->getStartTime() instanceof \DateTime) {
            throw new \RuntimeException('StartTime should be an instance of \DateTime');
        }
        if (! $recurrence->getEndTime() instanceof \DateTime) {
            throw new \RuntimeException('EndTime should be an instance of \DateTime');
        }
        if (in_array(
            $recurrence->getRecurrenceType(),
            [
                Recurrence::TYPE_MONTH_N_TH,
                Recurrence::TYPE_YEAR_N_TH,
            ],
            true
        )) {
            if (! array_key_exists($recurrence->getInstance(), self::$instanceRelativeValues)) {
                throw new \RuntimeException('Unknown instance');
            }
        }
    }

    /**
     * Returns relative value for dayOfWeek of recurrence entity.
     *
     * @param array $dayOfWeek
     *
     * @return string
     */
    public function getDayOfWeekRelativeValue($dayOfWeek)
    {
        sort($dayOfWeek);
        sort(self::$weekends);
        if (self::$weekends == $dayOfWeek) {
            return 'weekend';
        }

        sort(self::$weekdays);
        if (self::$weekdays == $dayOfWeek) {
            return 'weekday';
        }

        if (count($dayOfWeek) == 7) {
            return 'day';
        }

        //returns first element
        return reset($dayOfWeek);
    }
}
