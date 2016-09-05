<?php

namespace Oro\Bundle\CalendarBundle\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Model\Recurrence;

class MonthlyStrategy extends AbstractStrategy
{
    /**
     * {@inheritdoc}
     */
    public function getOccurrences(Entity\Recurrence $recurrence, \DateTime $start, \DateTime $end)
    {
        $result = [];
        $occurrenceDate = $this->getFirstOccurrence($recurrence);
        $interval = $recurrence->getInterval();
        $fromStartInterval = 1;
        $dayOfMonth = $recurrence->getDayOfMonth();

        if ($start > $occurrenceDate) {
            $dateInterval = $start->diff($occurrenceDate);
            $fromStartInterval = (int)$dateInterval->format('%y') * 12 + (int)$dateInterval->format('m');
            $fromStartInterval = floor($fromStartInterval / $interval);
            $occurrenceDate = $this->getNextOccurrence($fromStartInterval++ * $interval, $dayOfMonth, $occurrenceDate);
        }

        $occurrences = $recurrence->getOccurrences();
        while ($occurrenceDate <= $recurrence->getCalculatedEndTime()
            && $occurrenceDate <= $end
            && ($occurrences === null || $fromStartInterval <= $occurrences)
        ) {
            if ($occurrenceDate >= $start) {
                $result[] = $occurrenceDate;
            }
            $fromStartInterval++;
            $occurrenceDate = $this->getNextOccurrence($interval, $dayOfMonth, $occurrenceDate);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Entity\Recurrence $recurrence)
    {
        return $recurrence->getRecurrenceType() === Recurrence::TYPE_MONTHLY;
    }

    /**
     * {@inheritdoc}
     */
    public function getTextValue(Entity\Recurrence $recurrence)
    {
        $interval = $recurrence->getInterval();

        return $this->getFullRecurrencePattern(
            $recurrence,
            'oro.calendar.recurrence.patterns.monthly',
            $interval,
            ['%count%' => $interval, '%day%' => $recurrence->getDayOfMonth()]
        );
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
     * @param integer $interval A number of months.
     * @param integer $dayOfMonth
     * @param \DateTime $date
     *
     * @return \DateTime
     */
    protected function getNextOccurrence($interval, $dayOfMonth, \DateTime $date)
    {
        $currentDate = clone $date;
        $currentDate->setDate($currentDate->format('Y'), $currentDate->format('m'), 1);

        $result = new \DateTime("+{$interval} month {$currentDate->format('c')}");
        $daysInMonth = (int)$result->format('t');
        $dayOfMonth = ($daysInMonth < $dayOfMonth) ? $daysInMonth : $dayOfMonth;
        $result->setDate($result->format('Y'), $result->format('m'), $dayOfMonth);

        return $result;
    }

    /**
     * Returns first occurrence according to recurrence rules.
     *
     * @param Entity\Recurrence $recurrence
     *
     * @return \DateTime
     */
    protected function getFirstOccurrence(Entity\Recurrence $recurrence)
    {
        $dayOfMonth = $recurrence->getDayOfMonth();
        $occurrenceDate = clone $recurrence->getStartTime();
        $daysInMonth = (int)$occurrenceDate->format('t');
        $dayOfMonth = ($daysInMonth < $dayOfMonth) ? $daysInMonth : $dayOfMonth;
        $occurrenceDate->setDate($occurrenceDate->format('Y'), $occurrenceDate->format('m'), $dayOfMonth);

        if ($occurrenceDate->format('d') < $recurrence->getStartTime()->format('d')) {
            $occurrenceDate = $this->getNextOccurrence(
                $recurrence->getInterval(),
                $recurrence->getDayOfMonth(),
                $occurrenceDate
            );
        }

        return $occurrenceDate;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastOccurrence(Entity\Recurrence $recurrence)
    {
        $occurrenceDate = $this->getFirstOccurrence($recurrence);

        return $this->getNextOccurrence(
            ($recurrence->getOccurrences() - 1) * $recurrence->getInterval(),
            $recurrence->getDayOfMonth(),
            $occurrenceDate
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getValidationErrorMessage(Entity\Recurrence $recurrence)
    {
        if (!$recurrence->getDayOfMonth()) {
            return "Parameter 'dayOfMonth' can't be empty for Monthly recurrence pattern.";
        }

        return null;
    }
}
