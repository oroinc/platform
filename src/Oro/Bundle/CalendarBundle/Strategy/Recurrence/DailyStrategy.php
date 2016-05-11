<?php

namespace Oro\Bundle\CalendarBundle\Strategy\Recurrence;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;

class DailyStrategy extends AbstractStrategy implements StrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOccurrences(Recurrence $recurrence, \DateTime $start, \DateTime $end)
    {
        $this->strategyHelper->validateRecurrence($recurrence);
        $result = [];
        $occurrenceDate = $recurrence->getStartTime();
        $fromStartInterval = 1;
        if ($start > $occurrenceDate) {
            $dateInterval = $start->diff($occurrenceDate);
            $fromStartInterval = floor($dateInterval->format('%a') / $recurrence->getInterval());
            $occurrenceDate = $this->getNextOccurrence(
                $fromStartInterval++ * $recurrence->getInterval(),
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
            $occurrenceDate = $this->getNextOccurrence($recurrence->getInterval(), $occurrenceDate);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Recurrence $recurrence)
    {
        return $recurrence->getRecurrenceType() === Recurrence::TYPE_DAILY;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecurrencePattern(Recurrence $recurrence)
    {
        $interval = $recurrence->getInterval();

        return $this->getFullRecurrencePattern(
            $recurrence,
            'oro.calendar.recurrence.patterns.daily',
            $interval,
            ['%count%' => $interval]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'recurrence_daily';
    }

    /**
     * Returns occurrence date according to last occurrence date and recurrence interval.
     *
     * @param integer $interval A number of days.
     * @param \DateTime $date
     *
     * @return \DateTime
     */
    protected function getNextOccurrence($interval, \DateTime $date)
    {
        return new \DateTime("+{$interval} day {$date->format('c')}");
    }

    /**
     * {@inheritdoc}
     */
    public function getLastOccurrence(Recurrence $recurrence)
    {
        return $this->getNextOccurrence(
            $recurrence->getInterval() * ($recurrence->getOccurrences() - 1),
            $recurrence->getStartTime()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getValidationErrorMessage(Recurrence $recurrence)
    {
        //for this strategy no additional validation needed
        return null;
    }
}
