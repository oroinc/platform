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
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * According to recurrenceType it determines what recurrence strategy must be used for
     * calculating new calendar event occurrences, getting textual representation etc.
     * Possible values: daily, weekly, monthly, monthnth, yearly, yearnth.
     *
     * @var string
     *
     * @ORM\Column(name="recurrence_type", type="string", length=16)
     */
    protected $recurrenceType;

    /**
     * Contains number of units how often recurring events must repeat.
     * For example, 'every X days', 'every X weeks', 'every X months' (where X is interval).
     *
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
     * Contains a value from 1 to 5, that is relative value for 'first', 'second',
     * 'third', 'fourth' and 'last'.
     * It is used in monthnth and yearnth strategies, for creating
     * recurring events like 'Yearly every 2 years on the first Saturday of April',
     * 'Monthly the fourth Saturday of every 2 months',
     * 'Yearly every 2 years on the last Saturday of April'
     *
     * @var int
     *
     * @ORM\Column(name="instance", type="integer", nullable=true)
     */
    protected $instance;

    /**
     * Contains array of weekdays.
     * Possible values: 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'.
     * For relative 'weekday' value the array will be ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'].
     * For relative 'weekend' value the array will be ['sunday', 'saturday'].
     * For relative 'any day' value
     * the array will be ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'].
     *
     * @var []
     *
     * @ORM\Column(name="day_of_week", type="array", nullable=true)
     */
    protected $dayOfWeek;

    /**
     * Contains day of month that is used by monthly and yearly strategies.
     *
     * @var int
     *
     * @ORM\Column(name="day_of_month", type="integer", nullable=true)
     */
    protected $dayOfMonth;

    /**
     * Contains month number that is used by yearly and yearnth strategies.
     *
     * @var int
     *
     * @ORM\Column(name="month_of_year", type="integer", nullable=true)
     */
    protected $monthOfYear;

    /**
     * Start datetime for range of recurrence.
     *
     * @var \DateTime
     *
     * @ORM\Column(name="start_time", type="datetime")
     */
    protected $startTime;

    /**
     * End datetime for range of recurrence.
     *
     * @var \DateTime
     *
     * @ORM\Column(name="end_time", type="datetime", nullable=true)
     */
    protected $endTime;

    /**
     * Contains additionally calculated end datetime for range of recurrence.
     * It is used for SQL query and recurrence strategies optimization.
     *
     * @var \DateTime
     *
     * @ORM\Column(name="additional_end_time", type="datetime")
     */
    protected $additionalEndTime;

    /**
     * Contains the number of occurrences for range of recurrence.
     * It means that recurrence ends after X occurrences, where X is 'occurrences' value.
     *
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
     * @param \DateTime|null $additionalEndTime
     *
     * @return self
     */
    public function setAdditionalEndTime($additionalEndTime)
    {
        $this->additionalEndTime = $additionalEndTime;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getAdditionalEndTime()
    {
        return $this->additionalEndTime;
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
