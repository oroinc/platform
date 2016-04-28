<?php

namespace Oro\Bundle\CalendarBundle\Strategy\Recurrence;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Strategy\Recurrence\Helper\StrategyHelper;

/**
 * Recurrence with type Recurrence::TYPE_YEAR_N_TH will provide interval a number of month, which is multiple of 12.
 */
class YearNthStrategy implements StrategyInterface
{
    /** @var StrategyHelper */
    protected $strategyHelper;

    /**
     * @param StrategyHelper $strategyHelper
     */
    public function __construct(StrategyHelper $strategyHelper)
    {
        $this->strategyHelper = $strategyHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getOccurrences(Recurrence $recurrence, \DateTime $start, \DateTime $end)
    {
        $this->strategyHelper->validateRecurrence($recurrence);
        $result = [];
        $startTime = $recurrence->getStartTime();
        $dayOfWeek = $recurrence->getDayOfWeek();
        $monthOfYear = $recurrence->getMonthOfYear();
        $instance = $recurrence->getInstance();
        $occurrenceDate = $this->getNextOccurrence(0, $dayOfWeek, $monthOfYear, $instance, $startTime);

        if ($occurrenceDate < $recurrence->getStartTime()) {
            $occurrenceDate = $this->getNextOccurrence(
                $recurrence->getInterval(),
                $dayOfWeek,
                $monthOfYear,
                $instance,
                $occurrenceDate
            );
        }

        $interval = $recurrence->getInterval(); // a number of months, which is a multiple of 12
        $fromStartInterval = 1;

        if ($start > $occurrenceDate) {
            $dateInterval = $start->diff($occurrenceDate);
            $fromStartInterval = (int)$dateInterval->format('%y') * 12 + (int)$dateInterval->format('m');
            $fromStartInterval = floor($fromStartInterval / $interval);
            $occurrenceDate = $this->getNextOccurrence(
                $fromStartInterval++ * $interval,
                $dayOfWeek,
                $monthOfYear,
                $instance,
                $occurrenceDate
            );
        }

        $occurrences = $recurrence->getOccurrences();
        while ($occurrenceDate <= $recurrence->getEndTime()
            && $occurrenceDate <= $end
            && ($occurrences === null || $fromStartInterval <= $occurrences)
        ) {
            if ($occurrenceDate >= $start) {
                $result[] = $occurrenceDate;
            }
            $fromStartInterval++;
            $occurrenceDate = $this->getNextOccurrence($interval, $dayOfWeek, $monthOfYear, $instance, $occurrenceDate);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Recurrence $recurrence)
    {
        return $recurrence->getRecurrenceType() === Recurrence::TYPE_YEAR_N_TH;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecurrencePattern(Recurrence $recurrence)
    {
        return 'yearnth';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'recurrence_yearnth';
    }

    /**
     * Returns occurrence date according to last occurrence date and recurrence rules.
     *
     * @param integer $interval A number of months, which is a multiple of 12.
     * @param array $dayOfWeek
     * @param integer $monthOfYear
     * @param integer $instance
     * @param \DateTime $date
     *
     * @return \DateTime
     */
    protected function getNextOccurrence($interval, $dayOfWeek, $monthOfYear, $instance, \DateTime $date)
    {
        $occurrenceDate = new \DateTime("+{$interval} month {$date->format('c')}");

        $instanceRelativeValue = $this->strategyHelper->getInstanceRelativeValue($instance);
        $month = date('M', mktime(0, 0, 0, $monthOfYear));
        $year = $occurrenceDate->format('Y');
        $nextDays = [];
        foreach ($dayOfWeek as $day) {
            $nextDays[] = new \DateTime("{$instanceRelativeValue} {$day} of {$month} {$year}");
        }

        return $instance === Recurrence::INSTANCE_LAST ? max($nextDays) : min($nextDays);
    }
}
