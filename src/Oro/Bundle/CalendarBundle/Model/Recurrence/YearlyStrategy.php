<?php

namespace Oro\Bundle\CalendarBundle\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Model\Recurrence;

/**
 * Recurrence with type Recurrence::TYPE_YEARLY will provide interval a number of month, which is multiple of 12.
 */
class YearlyStrategy extends MonthlyStrategy
{
    /**
     * {@inheritdoc}
     */
    public function supports(Entity\Recurrence $recurrence)
    {
        return $recurrence->getRecurrenceType() === Recurrence::TYPE_YEARLY;
    }

    /**
     * {@inheritdoc}
     */
    public function getTextValue(Entity\Recurrence $recurrence)
    {
        $interval = (int)($recurrence->getInterval() / 12);
        $currentDate = new \DateTime('now', $this->getTimeZone());
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
    protected function getFirstOccurrence(Entity\Recurrence $recurrence)
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
    public function getValidationErrorMessage(Entity\Recurrence $recurrence)
    {
        if ($recurrence->getInterval() % 12 !== 0) {
            return "Parameter 'interval' value must be a multiple of 12 for Yearly recurrence pattern.";
        }

        if (!$recurrence->getDayOfMonth()) {
            return "Parameter 'dayOfMonth' can't be empty for Yearly recurrence pattern.";
        }

        if (!$recurrence->getMonthOfYear()) {
            return "Parameter 'monthOfYear' can't be empty for Yearly recurrence pattern.";
        }

        $currentDate = new \DateTime('now', $this->getTimeZone());
        $dateString = $currentDate->format('Y')
            . '-' . $recurrence->getMonthOfYear()
            . '-' . $recurrence->getDayOfMonth();

        // Try to create DateTime object for checking if day/month of recurrence is valid.
        try {
            new \DateTime($dateString, $this->getTimeZone());
        } catch (\Exception $exception) {
            return "Parameters 'dayOfMonth' and 'monthOfYear' values are invalid:"
            . " such date doesn't exist(Yearly recurrence pattern).";
        }

        return null;
    }
}
