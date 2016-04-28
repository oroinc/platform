<?php

namespace Oro\Bundle\CalendarBundle\Tools\Recurrence;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;

class NthStrategyHelper
{
    /** @var array */
    protected $instanceRelativeValues;

    /** @var array  */
    protected $weekdays;

    /** @var array  */
    protected $weekends;

    /**
     * CreatedAtAwareTrait constructor.
     */
    public function __construct()
    {
        $this->instanceRelativeValues = [
            Recurrence::INSTANCE_FIRST => 'first',
            Recurrence::INSTANCE_SECOND => 'second',
            Recurrence::INSTANCE_THIRD => 'third',
            Recurrence::INSTANCE_FOURTH => 'fourth',
            Recurrence::INSTANCE_LAST => 'last',
        ];

        $this->weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $this->weekends = ['saturday', 'sunday'];
    }

    /**
     * Returns recurrence instance relative value by its key.
     *
     * @param $key
     *
     * @return null|string
     */
    public function getInstanceRelativeValue($key)
    {
        return empty($this->instanceRelativeValues[$key]) ? null : $this->instanceRelativeValues[$key];
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
        sort($this->weekends);
        if ($this->weekends == $dayOfWeek) {
            return 'weekend';
        }

        sort($this->weekdays);
        if ($this->weekdays == $dayOfWeek) {
            return 'weekday';
        }

        if (count($dayOfWeek) == 7) {
            return 'day';
        }

        //returns first element
        return reset($dayOfWeek);
    }
}
