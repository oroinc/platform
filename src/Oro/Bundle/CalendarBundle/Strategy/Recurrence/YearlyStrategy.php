<?php

namespace Oro\Bundle\CalendarBundle\Strategy\Recurrence;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;

/**
 * Recurrence with type Recurrence::TYPE_YEARLY will provide interval a number of month, which is multiple of 12.
 */
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
        $interval = (int)($recurrence->getInterval() / 12);
        $currentDate = new \DateTime();
        $currentDate->setDate($currentDate->format('Y'), $recurrence->getMonthOfYear(), $recurrence->getDayOfMonth());
        $date = $this->dateTimeFormatter->formatDay($currentDate);

        return $this->getFullRecurrencePattern(
            $recurrence,
            'oro.calendar.recurrence.patterns.yearly',
            $interval,
            ['%count%' => $interval, '%day%' => $date]
        );
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
        $interval = $recurrence->getInterval(); // a number of months, which is a multiple of 12
        $occurrenceDate = clone $recurrence->getStartTime();
        $occurrenceDate->setDate($occurrenceDate->format('Y'), $monthOfYear, $dayOfMonth);

        if ($occurrenceDate < $recurrence->getStartTime()) {
            $occurrenceDate = $this->getNextOccurrence($interval, $occurrenceDate);
        }

        return $occurrenceDate;
    }

    /**
     * {@inheritdoc}
     */
    public function getValidationErrorMessage(Recurrence $recurrence)
    {
        if ($recurrence->getInterval() % 12 !== 0) {
            return "interval value must be a multiple of 12 for Yearly recurrence pattern";
        }

        if (empty($recurrence->getDayOfMonth())) {
            return "dayOfMonth can't be empty for Yearly recurrence pattern";
        }

        if (empty($recurrence->getMonthOfYear())) {
            return "monthOfYear can't be empty for Yearly recurrence pattern";
        }

        $currentDate = new \DateTime();
        $dateString = $currentDate->format('Y')
            . '-' . $recurrence->getMonthOfYear()
            . '-' . $recurrence->getDayOfMonth();
        if (\DateTime::createFromFormat('Y-m-d', $dateString) === FALSE) {
            return "dayOfMonth and monthOfYear values are invalid: such date doesn't exist(Yearly recurrence pattern)";
        }

        return null;
    }
}
