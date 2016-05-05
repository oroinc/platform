<?php

namespace Oro\Bundle\CalendarBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Recurrence Entity.
 *
 * @ORM\Table(
 *     name="oro_calendar_recurrence",
 *      indexes={
 *          @ORM\Index(name="oro_calendar_r_start_time_idx", columns={"start_time"}),
 *          @ORM\Index(name="oro_calendar_r_end_time_idx", columns={"end_time"})
 *      }
 * )
 * @ORM\Entity
 */
class Recurrence
{
    const STRING_KEY = 'recurrence';
    const MAX_END_DATE = '9000-01-01T00:00:01+00:00';

    const TYPE_DAILY = 'daily';
    const TYPE_WEEKLY = 'weekly';
    const TYPE_MONTHLY = 'monthly';
    const TYPE_MONTH_N_TH = 'monthnth';
    const TYPE_YEARLY = 'yearly';
    const TYPE_YEAR_N_TH = 'yearnth';

    const INSTANCE_FIRST = 1;
    const INSTANCE_SECOND = 2;
    const INSTANCE_THIRD = 3;
    const INSTANCE_FOURTH = 4;
    const INSTANCE_LAST = 5;
    
    const DAY_SUNDAY = 'sunday';
    const DAY_MONDAY = 'monday';
    const DAY_TUESDAY = 'tuesday';
    const DAY_WEDNESDAY = 'wednesday';
    const DAY_THURSDAY = 'thursday';
    const DAY_FRIDAY = 'friday';
    const DAY_SATURDAY = 'saturday';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="recurrence_type", type="string", length=16)
     */
    protected $recurrenceType;

    /**
     * @var int
     *
     * Units of this attribute depend of recurrenceType.
     * For daily recurrence it is number of days.
     * For weekly recurrence it is number of weeks.
     * For monthly, monthnth recurrences it is number of months.
     * For yearly, yearnth recurrences it is number of month, which is multiple of 12. I.e. 12, 24, 36 etc.
     *
     * @ORM\Column(name="`interval`", type="integer")
     */
    protected $interval;

    /**
     * @var int
     *
     * @ORM\Column(name="instance", type="integer", nullable=true)
     */
    protected $instance;

    /**
     * @var []
     *
     * @ORM\Column(name="day_of_week", type="array", nullable=true)
     */
    protected $dayOfWeek;

    /**
     * @var int
     *
     * @ORM\Column(name="day_of_month", type="integer", nullable=true)
     */
    protected $dayOfMonth;

    /**
     * @var int
     *
     * @ORM\Column(name="month_of_year", type="integer", nullable=true)
     */
    protected $monthOfYear;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_time", type="datetime")
     */
    protected $startTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_time", type="datetime", nullable=true)
     */
    protected $endTime;

    /**
     * @var int
     *
     * @ORM\Column(name="occurrences", type="integer", nullable=true)
     */
    protected $occurrences;

    /**
     * Gets id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets recurrenceType.
     *
     * @param string $recurrenceType
     *
     * @return self
     */
    public function setRecurrenceType($recurrenceType)
    {
        $this->recurrenceType = $recurrenceType;

        return $this;
    }

    /**
     * Gets recurrenceType.
     *
     * @return string
     */
    public function getRecurrenceType()
    {
        return $this->recurrenceType;
    }

    /**
     * Sets interval.
     *
     * @param integer $interval
     *
     * @return self
     */
    public function setInterval($interval)
    {
        $this->interval = $interval;

        return $this;
    }

    /**
     * Gets interval.
     *
     * @return integer
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /**
     * Sets instance.
     *
     * @param integer|null $instance
     *
     * @return self
     */
    public function setInstance($instance)
    {
        $this->instance = $instance;

        return $this;
    }

    /**
     * Gets instance.
     *
     * @return integer|null
     */
    public function getInstance()
    {
        return $this->instance;
    }

    /**
     * Sets dayOfWeek.
     *
     * @param array|null $dayOfWeek
     *
     * @return self
     */
    public function setDayOfWeek($dayOfWeek)
    {
        $this->dayOfWeek = $dayOfWeek;

        return $this;
    }

    /**
     * Gets dayOfWeek.
     *
     * @return array|null
     */
    public function getDayOfWeek()
    {
        return $this->dayOfWeek;
    }

    /**
     * Sets dayOfMonth.
     *
     * @param integer|null $dayOfMonth
     *
     * @return self
     */
    public function setDayOfMonth($dayOfMonth)
    {
        $this->dayOfMonth = $dayOfMonth;

        return $this;
    }

    /**
     * Gets dayOfMonth.
     *
     * @return integer|null
     */
    public function getDayOfMonth()
    {
        return $this->dayOfMonth;
    }

    /**
     * Sets monthOfYear.
     *
     * @param integer|null $monthOfYear
     *
     * @return self
     */
    public function setMonthOfYear($monthOfYear)
    {
        $this->monthOfYear = $monthOfYear;

        return $this;
    }

    /**
     * Gets monthOfYear.
     *
     * @return integer|null
     */
    public function getMonthOfYear()
    {
        return $this->monthOfYear;
    }

    /**
     * Sets startTime.
     *
     * @param \DateTime $startTime
     *
     * @return self
     */
    public function setStartTime($startTime)
    {
        $this->startTime = $startTime;

        return $this;
    }

    /**
     * Gets startTime.
     *
     * @return \DateTime
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * Sets endTime.
     *
     * @param \DateTime|null $endTime
     *
     * @return self
     */
    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * Gets endTime.
     *
     * @return \DateTime|null
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * Sets occurrences.
     *
     * @param integer|null $occurrences
     *
     * @return self
     */
    public function setOccurrences($occurrences)
    {
        $this->occurrences = $occurrences;

        return $this;
    }

    /**
     * Gets occurrences.
     *
     * @return integer|null
     */
    public function getOccurrences()
    {
        return $this->occurrences;
    }
}
