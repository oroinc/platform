<?php

namespace Oro\Bundle\CalendarBundle\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Model\Recurrence;

/**
 * Recurrence with type Recurrence::TYPE_YEAR_N_TH will provide interval a number of month, which is multiple of 12.
 */
class YearNthStrategy extends AbstractStrategy
{
    /**
     * {@inheritdoc}
     */
    public function getOccurrences(Entity\Recurrence $recurrence, \DateTime $start, \DateTime $end)
    {
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
        while ($occurrenceDate <= $recurrence->getCalculatedEndTime()
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
    public function supports(Entity\Recurrence $recurrence)
    {
        return $recurrence->getRecurrenceType() === Recurrence::TYPE_YEAR_N_TH;
    }

    /**
     * {@inheritdoc}
     */
    public function getTextValue(Entity\Recurrence $recurrence)
    {
        $interval = (int)($recurrence->getInterval() / 12);
        $instanceValue = $this->getInstanceRelativeValue($recurrence->getInstance());
        $instance = $this->translator->trans('oro.calendar.recurrence.instances.' . $instanceValue);
        $day = $this->getDayOfWeekRelativeValue($recurrence->getDayOfWeek());
        $day = $this->translator->trans('oro.calendar.recurrence.days.' . $day);
        $currentDate = new \DateTime('now', $recurrence->getStartTime()->getTimezone());
        $currentDate->setDate($currentDate->format('Y'), $recurrence->getMonthOfYear(), $currentDate->format('d'));
        $month = $this->dateTimeFormatter->format($currentDate, null, \IntlDateFormatter::NONE, null, null, 'MMM');

        return $this->getFullRecurrencePattern(
            $recurrence,
            'oro.calendar.recurrence.patterns.yearnth',
            $interval,
            ['%count%' => $interval, '%day%' => $day, '%instance%' => strtolower($instance), '%month%' => $month]
        );
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

        $instanceRelativeValue = $this->getInstanceRelativeValue($instance);
        $month = date('M', mktime(0, 0, 0, $monthOfYear));
        $year = $occurrenceDate->format('Y');
        $time = $occurrenceDate->format('H:i:s.u');
        $nextDays = [];
        if ($instance === Recurrence::INSTANCE_FIRST || $instance === Recurrence::INSTANCE_LAST) {
            foreach ($dayOfWeek as $day) {
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
            foreach ($dayOfWeek as $day) {
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

        return $this->getNextOccurrence(
            ($recurrence->getOccurrences() - 1) * $recurrence->getInterval(),
            $dayOfWeek,
            $monthOfYear,
            $instance,
            $occurrenceDate
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getValidationErrorMessage(Entity\Recurrence $recurrence)
    {
        if ($recurrence->getInterval() % 12 !== 0) {
            return "Parameter 'interval' value must be a multiple of 12 for YearNth recurrence pattern.";
        }

        if (!$recurrence->getInstance()) {
            return "Parameter 'instance' value can't be empty for YearNth recurrence pattern.";
        }

        if (!$recurrence->getDayOfWeek()) {
            return "Parameter 'dayOfWeek' can't be empty for YearNth recurrence pattern.";
        }

        if (!$recurrence->getMonthOfYear()) {
            return "Parameter 'monthOfYear' can't be empty for YearNth recurrence pattern.";
        }

        return null;
    }
}
