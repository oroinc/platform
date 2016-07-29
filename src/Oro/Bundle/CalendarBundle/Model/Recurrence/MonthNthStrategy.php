<?php

namespace Oro\Bundle\CalendarBundle\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Model\Recurrence;

class MonthNthStrategy extends AbstractStrategy
{
    /**
     * {@inheritdoc}
     */
    public function getOccurrences(Entity\Recurrence $recurrence, \DateTime $start, \DateTime $end)
    {
        $result = [];
        $dayOfWeek = $recurrence->getDayOfWeek();
        if ($dayOfWeek === null || count($dayOfWeek) === 0) {
            return $result;
        }
        $startTime = $recurrence->getStartTime();
        $instance = $recurrence->getInstance();
        $occurrenceDate = $this->getNextOccurrence(0, $dayOfWeek, $instance, $startTime);

        if ($occurrenceDate < $recurrence->getStartTime()) {
            $occurrenceDate = $this->getNextOccurrence(
                $recurrence->getInterval(),
                $dayOfWeek,
                $instance,
                $occurrenceDate
            );
        }

        $interval = $recurrence->getInterval();
        $fromStartInterval = 1;

        if ($start > $occurrenceDate) {
            $dateInterval = $start->diff($occurrenceDate);
            $fromStartInterval = (int)$dateInterval->format('%y') * 12 + (int)$dateInterval->format('%m');
            $fromStartInterval = floor($fromStartInterval / $interval);
            $occurrenceDate = $this->getNextOccurrence(
                $fromStartInterval++ * $interval,
                $dayOfWeek,
                $instance,
                $occurrenceDate
            );
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
            $occurrenceDate = $this->getNextOccurrence($interval, $dayOfWeek, $instance, $occurrenceDate);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Entity\Recurrence $recurrence)
    {
        return $recurrence->getRecurrenceType() === Recurrence::TYPE_MONTH_N_TH;
    }

    /**
     * {@inheritdoc}
     */
    public function getTextValue(Entity\Recurrence $recurrence)
    {
        $interval = $recurrence->getInterval();
        $instanceValue = $this->getInstanceRelativeValue($recurrence->getInstance());
        $instance = $this->translator->trans('oro.calendar.recurrence.instances.' . $instanceValue);
        $day = $this->getDayOfWeekRelativeValue($recurrence->getDayOfWeek());
        $day = $this->translator->trans('oro.calendar.recurrence.days.' . $day);

        return $this->getFullRecurrencePattern(
            $recurrence,
            'oro.calendar.recurrence.patterns.monthnth',
            $interval,
            ['%count%' => $interval, '%day%' => $day, '%instance%' => strtolower($instance)]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'recurrence_monthnth';
    }

    /**
     * Returns occurrence date according to last occurrence date and recurrence rules.
     *
     * @param integer $interval A number of months.
     * @param array $daysOfWeek
     * @param integer $instance
     * @param \DateTime $date
     *
     * @return \DateTime
     */
    protected function getNextOccurrence($interval, $daysOfWeek, $instance, \DateTime $date)
    {
        $occurrenceDate = new \DateTime("+{$interval} month {$date->format('c')}");

        $instanceRelativeValue = $this->getInstanceRelativeValue($instance);
        $month = $occurrenceDate->format('M');
        $year = $occurrenceDate->format('Y');
        $time = $occurrenceDate->format('H:i:s.u');
        $nextDays = [];
        if ($instance === Recurrence::INSTANCE_FIRST || $instance === Recurrence::INSTANCE_LAST) {
            foreach ($daysOfWeek as $day) {
                $nextDays[] = new \DateTime(
                    "{$instanceRelativeValue} {$day} of {$month} {$year} {$time}",
                    $occurrenceDate->getTimezone()
                );
            }

            return $instance === Recurrence::INSTANCE_LAST ? max($nextDays) : min($nextDays);
        }

        $days = [];
        $currentInstance = 1;
        while (count($days) < $instance) {
            $instanceRelativeValue = $this->getInstanceRelativeValue($currentInstance);
            foreach ($daysOfWeek as $day) {
                $days[] = new \DateTime(
                    "{$instanceRelativeValue} {$day} of {$month} {$year} {$time}",
                    $occurrenceDate->getTimezone()
                );
            }
            $currentInstance++;
        }
        sort($days);

        return $days[$instance - 1];
    }

    /**
     * {@inheritdoc}
     */
    public function getLastOccurrence(Entity\Recurrence $recurrence)
    {
        $dayOfWeek = $recurrence->getDayOfWeek();
        $startTime = $recurrence->getStartTime();
        $instance = $recurrence->getInstance();
        $occurrenceDate = $this->getNextOccurrence(0, $dayOfWeek, $instance, $startTime);

        if ($occurrenceDate < $recurrence->getStartTime()) {
            $occurrenceDate = $this->getNextOccurrence(
                $recurrence->getInterval(),
                $dayOfWeek,
                $instance,
                $occurrenceDate
            );
        }

        return  $this->getNextOccurrence(
            ($recurrence->getOccurrences() - 1) * $recurrence->getInterval(),
            $dayOfWeek,
            $instance,
            $occurrenceDate
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getValidationErrorMessage(Entity\Recurrence $recurrence)
    {
        if (!$recurrence->getInstance()) {
            return "Parameter 'instance' value can't be empty for MonthNth recurrence pattern.";
        }

        if (!$recurrence->getDayOfWeek()) {
            return "Parameter 'dayOfWeek' can't be empty for MonthNth recurrence pattern.";
        }

        return null;
    }
}
