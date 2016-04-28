<?php

namespace Oro\Bundle\CalendarBundle\Strategy\Recurrence;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;

class MonthlyStrategy implements StrategyInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException
     */
    public function getOccurrences(Recurrence $recurrence, \DateTime $start, \DateTime $end)
    {
        // @TODO handle cases when Recurrence::$startTime = Recurrence::$endTime = null.
        $result = [];
        // @TODO extract validation into abstract class or strategy helper.
        if (false === filter_var($recurrence->getInterval(), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
            throw new \RuntimeException('Value should be integer with min_rage >= 1.');
        }
        $occurrenceDate = $this->getFirstOccurrence($recurrence);
        $interval = $recurrence->getInterval();
        $fromStartInterval = 1;

        if ($start > $occurrenceDate) {
            $dateInterval = $start->diff($occurrenceDate);
            $fromStartInterval = (int)$dateInterval->format('%y') * 12 + (int)$dateInterval->format('m');
            $fromStartInterval = floor($fromStartInterval / $interval);
            $occurrenceDate = $this->getNextOccurrence($fromStartInterval++ * $interval, $occurrenceDate);
        }

        $occurrences = $recurrence->getOccurrences();
        // @TODO extract condition retrievement into abstract class or strategy helper.
        while ($occurrenceDate <= $recurrence->getEndTime()
            && $occurrenceDate <= $end
            && ($occurrences === null || $fromStartInterval <= $occurrences)
        ) {
            if ($occurrenceDate >= $start) {
                $result[] = $occurrenceDate;
            }
            $fromStartInterval++;
            $occurrenceDate = $this->getNextOccurrence($interval, $occurrenceDate);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Recurrence $recurrence)
    {
        return $recurrence->getRecurrenceType() === Recurrence::TYPE_MONTHLY;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecurrencePattern(Recurrence $recurrence)
    {
        return 'monthly';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'recurrence_monthly';
    }

    /**
     * Returns occurrence date according to last occurrence date and recurrence interval.
     *
     * @param integer $interval
     * @param \DateTime $date
     *
     * @return \DateTime
     */
    protected function getNextOccurrence($interval, \DateTime $date)
    {
        return new \DateTime("+{$interval} month {$date->format('c')}");
    }

    /**
     * Returns first occurrence according to recurrence rules.
     *
     * @param Recurrence $recurrence
     *
     * @return \DateTime
     */
    protected function getFirstOccurrence(Recurrence $recurrence)
    {
        $dayOfMonth = $recurrence->getDayOfMonth();
        $occurrenceDate = $recurrence->getStartTime();
        $occurrenceDate->setDate($occurrenceDate->format('Y'), $occurrenceDate->format('m'), $dayOfMonth);

        if ($occurrenceDate->format('d') < $recurrence->getStartTime()->format('d')) {
            $occurrenceDate = $this->getNextOccurrence($recurrence->getInterval(), $occurrenceDate);
        }

        return $occurrenceDate;
    }
}
