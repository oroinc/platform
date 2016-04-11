<?php

namespace Oro\Bundle\CalendarBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Recurrence Entity
 *
 * @ORM\Table(name="oro_recurrence")
 * @ORM\Entity
 */
class Recurrence
{
    const TYPE_DAILY = 'daily';
    const TYPE_WEEKLY = 'weekly';
    const TYPE_MONTHLY = 'monthly';
    const TYPE_MONTH_N_TH = 'monthnth';
    const TYPE_YEARLY = 'yearly';
    const TYPE_YEAR_N_TH = 'yearnth';

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
     * @ORM\Column(name="interval", type="integer")
     */
    protected $interval;

    /**
     * @var int
     *
     * @ORM\Column(name="instance", type="integer")
     */
    protected $instance;

    /**
     * @var []
     *
     * @ORM\Column(name="day_of_week", type="array")
     */
    protected $dayOfWeek;

    /**
     * @var int
     *
     * @ORM\Column(name="day_of_month", type="integer")
     */
    protected $dayOfMonth;

    /**
     * @var int
     *
     * @ORM\Column(name="month_of_year", type="integer")
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
     * @ORM\Column(name="end_time", type="datetime")
     */
    protected $endTime;

    /**
     * @var int
     *
     * @ORM\Column(name="occurrences", type="integer")
     */
    protected $occurrences;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set recurrenceType
     *
     * @param string $recurrenceType
     *
     * @return Recurrence
     */
    public function setRecurrenceType($recurrenceType)
    {
        $this->recurrenceType = $recurrenceType;

        return $this;
    }

    /**
     * Get recurrenceType
     *
     * @return string
     */
    public function getRecurrenceType()
    {
        return $this->recurrenceType;
    }

    /**
     * Set interval
     *
     * @param integer $interval
     *
     * @return Recurrence
     */
    public function setInterval($interval)
    {
        $this->interval = $interval;

        return $this;
    }

    /**
     * Get interval
     *
     * @return integer
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /**
     * Set instance
     *
     * @param integer $instance
     *
     * @return Recurrence
     */
    public function setInstance($instance)
    {
        $this->instance = $instance;

        return $this;
    }

    /**
     * Get instance
     *
     * @return integer
     */
    public function getInstance()
    {
        return $this->instance;
    }

    /**
     * Set dayOfWeek
     *
     * @param array $dayOfWeek
     *
     * @return Recurrence
     */
    public function setDayOfWeek($dayOfWeek)
    {
        $this->dayOfWeek = $dayOfWeek;

        return $this;
    }

    /**
     * Get dayOfWeek
     *
     * @return array
     */
    public function getDayOfWeek()
    {
        return $this->dayOfWeek;
    }

    /**
     * Set dayOfMonth
     *
     * @param integer $dayOfMonth
     *
     * @return Recurrence
     */
    public function setDayOfMonth($dayOfMonth)
    {
        $this->dayOfMonth = $dayOfMonth;

        return $this;
    }

    /**
     * Get dayOfMonth
     *
     * @return integer
     */
    public function getDayOfMonth()
    {
        return $this->dayOfMonth;
    }

    /**
     * Set monthOfYear
     *
     * @param integer $monthOfYear
     *
     * @return Recurrence
     */
    public function setMonthOfYear($monthOfYear)
    {
        $this->monthOfYear = $monthOfYear;

        return $this;
    }

    /**
     * Get monthOfYear
     *
     * @return integer
     */
    public function getMonthOfYear()
    {
        return $this->monthOfYear;
    }

    /**
     * Set startTime
     *
     * @param \DateTime $startTime
     *
     * @return Recurrence
     */
    public function setStartTime($startTime)
    {
        $this->startTime = $startTime;

        return $this;
    }

    /**
     * Get startTime
     *
     * @return \DateTime
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * Set endTime
     *
     * @param \DateTime $endTime
     *
     * @return Recurrence
     */
    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * Get endTime
     *
     * @return \DateTime
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * Set occurrences
     *
     * @param integer $occurrences
     *
     * @return Recurrence
     */
    public function setOccurrences($occurrences)
    {
        $this->occurrences = $occurrences;

        return $this;
    }

    /**
     * Get occurrences
     *
     * @return integer
     */
    public function getOccurrences()
    {
        return $this->occurrences;
    }
}
