<?php

namespace Oro\Bundle\CalendarBundle\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;

class YearlyStrategy extends MonthlyStrategy
{
    /**
     * {@inheritdoc}
     */
    public function supports(Recurrence $recurrence)
    {
        return $recurrence->getRecurrenceType() === Recurrence::TYPE_YEARLY;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecurrencePattern(Recurrence $recurrence)
    {
        return 'yearly';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'recurrence_yearly';
    }

    /**
     * {@inheritdoc}
     */
    protected function getFirstOccurrence(Recurrence $recurrence)
    {
        $dayOfMonth = $recurrence->getDayOfMonth();
        $monthOfYear = $recurrence->getMonthOfYear();
        $interval = $recurrence->getInterval();
        $occurrenceDate = $recurrence->getStartTime();
        $occurrenceDate->setDate($occurrenceDate->format('Y'), $monthOfYear, $dayOfMonth);

        if ($occurrenceDate < $recurrence->getStartTime()) {
            $occurrenceDate = $this->getNextOccurrence($interval, $occurrenceDate);
        }

        return $occurrenceDate;
    }
}
