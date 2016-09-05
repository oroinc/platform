<?php

namespace Oro\Bundle\CalendarBundle\Model;

use Symfony\Component\Validator\Validator\ValidatorInterface;

use Oro\Bundle\CalendarBundle\Model\Recurrence\StrategyInterface;
use Oro\Bundle\CalendarBundle\Entity;

class Recurrence
{
    const STRING_KEY = 'recurrence';

    /**
     * Used to calculate max endTime when it's empty and there are no occurrences specified.
     *
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence\AbstractStrategy::getCalculatedEndTime
     */
    const MAX_END_DATE = '9000-01-01T00:00:01+00:00';

    /**#@+
     * Type of recurrence
     *
     * Respective strategies:
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence\DailyStrategy
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence\WeeklyStrategy
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence\MonthlyStrategy
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence\MonthNthStrategy
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence\YearlyStrategy
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence\YearNthStrategy
     *
     * Property which obtains one of these values:
     * @see \Oro\Bundle\CalendarBundle\Entity\Recurrence::$recurrenceType
     */
    const TYPE_DAILY = 'daily';
    const TYPE_WEEKLY = 'weekly';
    const TYPE_MONTHLY = 'monthly';
    const TYPE_MONTH_N_TH = 'monthnth';
    const TYPE_YEARLY = 'yearly';
    const TYPE_YEAR_N_TH = 'yearnth';
    /**#@-*/


    /**#@+
     * It is used in monthnth and yearnth strategies, for creating recurring events like:
     * 'Yearly every 2 years on the first Saturday of April',
     * 'Monthly the fourth Saturday of every 2 months',
     * 'Yearly every 2 years on the last Saturday of April'.
     *
     * Property which obtains one of these values:
     * @see \Oro\Bundle\CalendarBundle\Entity\Recurrence::$instance
     */
    const INSTANCE_FIRST = 1;
    const INSTANCE_SECOND = 2;
    const INSTANCE_THIRD = 3;
    const INSTANCE_FOURTH = 4;
    const INSTANCE_LAST = 5;
    /**#@-*/

    /**#@+
     * Constants of days used in recurrence.
     *
     * Property which obtains one of these values:
     * @see \Oro\Bundle\CalendarBundle\Entity\Recurrence::$dayOfWeek
     */
    const DAY_SUNDAY = 'sunday';
    const DAY_MONDAY = 'monday';
    const DAY_TUESDAY = 'tuesday';
    const DAY_WEDNESDAY = 'wednesday';
    const DAY_THURSDAY = 'thursday';
    const DAY_FRIDAY = 'friday';
    const DAY_SATURDAY = 'saturday';
    /**#@-*/

    /** @var ValidatorInterface */
    protected $validator;

    /** @var StrategyInterface  */
    protected $recurrenceStrategy;

    /** @var array */
    public static $instanceRelativeValues = [
        self::INSTANCE_FIRST => 'first',
        self::INSTANCE_SECOND => 'second',
        self::INSTANCE_THIRD => 'third',
        self::INSTANCE_FOURTH => 'fourth',
        self::INSTANCE_LAST => 'last',
    ];

    /** @var array */
    public static $weekdays = [
        self::DAY_MONDAY,
        self::DAY_TUESDAY,
        self::DAY_WEDNESDAY,
        self::DAY_THURSDAY,
        self::DAY_FRIDAY,
    ];

    /** @var array */
    public static $weekends = [
        self::DAY_SATURDAY,
        self::DAY_SUNDAY,
    ];

    /**
     * @param ValidatorInterface $validator
     * @param StrategyInterface $recurrenceStrategy
     */
    public function __construct(ValidatorInterface $validator, StrategyInterface $recurrenceStrategy)
    {
        $this->validator = $validator;
        $this->recurrenceStrategy = $recurrenceStrategy;
    }

    /**
     * @param Entity\Recurrence $recurrence
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return \DateTime[]
     */
    public function getOccurrences(Entity\Recurrence $recurrence, \DateTime $start, \DateTime $end)
    {
        return $this->recurrenceStrategy->getOccurrences($recurrence, $start, $end);
    }

    /**
     * @param Entity\Recurrence $recurrence
     *
     * @return string
     */
    public function getTextValue(Entity\Recurrence $recurrence)
    {
        return $this->recurrenceStrategy->getTextValue($recurrence);
    }

    /**
     * @param Entity\Recurrence $recurrence
     *
     * @return \DateTime
     */
    public function getCalculatedEndTime(Entity\Recurrence $recurrence)
    {
        return $this->recurrenceStrategy->getCalculatedEndTime($recurrence);
    }

    /**
     * @param Entity\Recurrence $recurrence
     *
     * @return null|string
     */
    public function getValidationErrorMessage(Entity\Recurrence $recurrence)
    {
        return $this->recurrenceStrategy->getValidationErrorMessage($recurrence);
    }

    /**
     * Returns the list of possible values for recurrenceType.
     *
     * @return array
     */
    public function getRecurrenceTypesValues()
    {
        return array_keys($this->getRecurrenceTypes());
    }

    /**
     * Returns the list of possible values for dayOfWeek.
     *
     * @return array
     */
    public function getDaysOfWeekValues()
    {
        return array_keys($this->getDaysOfWeek());
    }

    /**
     * Returns the list of possible values(with labels) for recurrenceType.
     *
     * @return array
     */
    public function getRecurrenceTypes()
    {
        return [
            self::TYPE_DAILY => 'oro.calendar.recurrence.types.daily',
            self::TYPE_WEEKLY => 'oro.calendar.recurrence.types.weekly',
            self::TYPE_MONTHLY => 'oro.calendar.recurrence.types.monthly',
            self::TYPE_MONTH_N_TH => 'oro.calendar.recurrence.types.monthnth',
            self::TYPE_YEARLY => 'oro.calendar.recurrence.types.yearly',
            self::TYPE_YEAR_N_TH => 'oro.calendar.recurrence.types.yearnth',
        ];
    }

    /**
     * Returns the list of possible values(with labels) for instance.
     *
     * @return array
     */
    public function getInstances()
    {
        return [
            self::INSTANCE_FIRST => 'oro.calendar.recurrence.instances.first',
            self::INSTANCE_SECOND => 'oro.calendar.recurrence.instances.second',
            self::INSTANCE_THIRD => 'oro.calendar.recurrence.instances.third',
            self::INSTANCE_FOURTH => 'oro.calendar.recurrence.instances.fourth',
            self::INSTANCE_LAST => 'oro.calendar.recurrence.instances.last',
        ];
    }

    /**
     * Returns the list of possible values(with labels) for dayOfWeek.
     *
     * @return array
     */
    public function getDaysOfWeek()
    {
        return [
            self::DAY_SUNDAY => 'oro.calendar.recurrence.days.sunday',
            self::DAY_MONDAY => 'oro.calendar.recurrence.days.monday',
            self::DAY_TUESDAY => 'oro.calendar.recurrence.days.tuesday',
            self::DAY_WEDNESDAY => 'oro.calendar.recurrence.days.wednesday',
            self::DAY_THURSDAY => 'oro.calendar.recurrence.days.thursday',
            self::DAY_FRIDAY => 'oro.calendar.recurrence.days.friday',
            self::DAY_SATURDAY => 'oro.calendar.recurrence.days.saturday',
        ];
    }
}
