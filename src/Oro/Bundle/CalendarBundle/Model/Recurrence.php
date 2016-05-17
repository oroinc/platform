<?php

namespace Oro\Bundle\CalendarBundle\Model;

use Oro\Bundle\CalendarBundle\Entity\Recurrence as EntityRecurrence;

class Recurrence
{
    /**
     * Returns the list of possible values for recurrenceType.
     *
     * @return array
     */
    public static function getRecurrenceTypesValues()
    {
        return [
            EntityRecurrence::TYPE_DAILY,
            EntityRecurrence::TYPE_WEEKLY,
            EntityRecurrence::TYPE_MONTHLY,
            EntityRecurrence::TYPE_MONTH_N_TH,
            EntityRecurrence::TYPE_YEARLY,
            EntityRecurrence::TYPE_YEAR_N_TH,
        ];
    }

    /**
     * Returns the list of possible values for dayOfWeek.
     *
     * @return array
     */
    public static function getDaysOfWeekValues()
    {
        return [
            EntityRecurrence::DAY_SUNDAY,
            EntityRecurrence::DAY_MONDAY,
            EntityRecurrence::DAY_TUESDAY,
            EntityRecurrence::DAY_WEDNESDAY,
            EntityRecurrence::DAY_THURSDAY,
            EntityRecurrence::DAY_FRIDAY,
            EntityRecurrence::DAY_SATURDAY,
        ];
    }

    /**
     * Returns the list of possible values(with labels) for recurrenceType.
     *
     * @return array
     */
    public static function getRecurrenceTypes()
    {
        return [
            EntityRecurrence::TYPE_DAILY => 'oro.calendar.recurrence.types.daily',
            EntityRecurrence::TYPE_WEEKLY => 'oro.calendar.recurrence.types.weekly',
            EntityRecurrence::TYPE_MONTHLY => 'oro.calendar.recurrence.types.monthly',
            EntityRecurrence::TYPE_MONTH_N_TH => 'oro.calendar.recurrence.types.monthnth',
            EntityRecurrence::TYPE_YEARLY => 'oro.calendar.recurrence.types.yearly',
            EntityRecurrence::TYPE_YEAR_N_TH => 'oro.calendar.recurrence.types.yearnth',
        ];
    }

    /**
     * Returns the list of possible values(with labels) for instance.
     *
     * @return array
     */
    public static function getInstances()
    {
        return [
            EntityRecurrence::INSTANCE_FIRST => 'oro.calendar.recurrence.instances.first',
            EntityRecurrence::INSTANCE_SECOND => 'oro.calendar.recurrence.instances.second',
            EntityRecurrence::INSTANCE_THIRD => 'oro.calendar.recurrence.instances.third',
            EntityRecurrence::INSTANCE_FOURTH => 'oro.calendar.recurrence.instances.fourth',
            EntityRecurrence::INSTANCE_LAST => 'oro.calendar.recurrence.instances.last',
        ];
    }

    /**
     * Returns the list of possible values(with labels) for dayOfWeek.
     *
     * @return array
     */
    public static function getDaysOfWeek()
    {
        return [
            EntityRecurrence::DAY_SUNDAY => 'oro.calendar.recurrence.days.sunday',
            EntityRecurrence::DAY_MONDAY => 'oro.calendar.recurrence.days.monday',
            EntityRecurrence::DAY_TUESDAY => 'oro.calendar.recurrence.days.tuesday',
            EntityRecurrence::DAY_WEDNESDAY => 'oro.calendar.recurrence.days.wednesday',
            EntityRecurrence::DAY_THURSDAY => 'oro.calendar.recurrence.days.thursday',
            EntityRecurrence::DAY_FRIDAY => 'oro.calendar.recurrence.days.friday',
            EntityRecurrence::DAY_SATURDAY => 'oro.calendar.recurrence.days.saturday',
        ];
    }
}
