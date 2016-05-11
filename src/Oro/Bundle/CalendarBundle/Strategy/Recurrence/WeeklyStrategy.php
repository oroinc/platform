<?php

namespace Oro\Bundle\CalendarBundle\Strategy\Recurrence;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;

class WeeklyStrategy extends AbstractStrategy implements StrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOccurrences(Recurrence $recurrence, \DateTime $start, \DateTime $end)
    {
        $this->strategyHelper->validateRecurrence($recurrence);
        $result = [];
        $weekDays = $recurrence->getDayOfWeek();

        if (empty($weekDays)) {
            return $result;
        }

        //week days should be sorted in standard sequence (sun, mon, tue...)
        $this->sortWeekDays($weekDays);

        $firstDay = reset($weekDays);
        $startTime = new \DateTime("previous $firstDay {$recurrence->getStartTime()->format('c')}");
        /** @var float $fromStartInterval */
        $fromStartInterval = 0;
        $interval = $recurrence->getInterval();
        $fullWeeks = 0;
        if ($start > $startTime) {
            $dateInterval = $start->diff($startTime);
            $fromStartInterval = floor(((int)$dateInterval->format('%a') + 1) / 7 / $interval) * count($weekDays);
            foreach ($weekDays as $day) {
                $currentDay = new \DateTime($day);
                if ($currentDay->format('w') < $recurrence->getStartTime()->format('w')) {
                    $fromStartInterval = $fromStartInterval == 0 ? $fromStartInterval : $fromStartInterval - 1;
                }
            }
            $fullWeeks = ceil($fromStartInterval / count($weekDays)) * $interval;
        }

        $afterFullWeeksDate = new \DateTime("+{$fullWeeks} week {$startTime->format('c')}");

        while ($afterFullWeeksDate <= $end && $afterFullWeeksDate <= $recurrence->getEndTime()) {
            foreach ($weekDays as $day) {
                $next = $this->getNextOccurrence($day, $afterFullWeeksDate);
                if ($next > $end
                    || $next > $recurrence->getEndTime()
                    || ($recurrence->getOccurrences() && $fromStartInterval >= $recurrence->getOccurrences())
                ) {
                    return $result;
                }

                if ($next >= $start
                    && $next <= $end
                    && $next >= $recurrence->getStartTime()
                    && $next <= $recurrence->getEndTime()
                ) {
                    $result[] = $next;
                }

                $fromStartInterval = $next >= $recurrence->getStartTime() ? $fromStartInterval +1 : $fromStartInterval;
            }
            $fullWeeks += $interval;
            $afterFullWeeksDate = new \DateTime("+{$fullWeeks} week {$startTime->format('c')}");
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Recurrence $recurrence)
    {
        return $recurrence->getRecurrenceType() === Recurrence::TYPE_WEEKLY;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecurrencePattern(Recurrence $recurrence)
    {
        $interval = $recurrence->getInterval();
        $days = [];
        foreach ($recurrence->getDayOfWeek() as $day) {
            $days[] = $this->translator->trans('oro.calendar.recurrence.days.' . $day);
        }

        return $this->getFullRecurrencePattern(
            $recurrence,
            'oro.calendar.recurrence.patterns.weekly',
            $interval,
            ['%count%' => $interval, '%days%' => implode(', ', $days)]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'recurrence_weekly';
    }

    /**
     * Returns next date occurrence.
     *
     * @param string $day
     * @param \DateTime $date
     *
     * @return \DateTime
     */
    protected function getNextOccurrence($day, \DateTime $date)
    {
        if (strtolower($date->format('l')) === strtolower($day)) {
            return $date;
        }

        return new \DateTime("next {$day} {$date->format('c')}");
    }

    /**
     * Sorts weekdays array to standard sequence(sun, mon, ...).
     *
     * @param $weekDays
     *
     * @return self
     */
    protected function sortWeekDays(&$weekDays)
    {
        usort($weekDays, function ($item1, $item2) {
            $date1 = new \DateTime($item1);
            $date2 = new \DateTime($item2);

            if ($date1->format('w') === $date2->format('w')) {
                return 0;
            }

            return $date1->format('w') > $date2->format('w');
        });

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastOccurrence(Recurrence $recurrence)
    {
        $weekDays = $recurrence->getDayOfWeek();

        $this->sortWeekDays($weekDays);
        $firstDay = reset($weekDays);
        $currentDay = new \DateTime($firstDay);
        $startTime = $recurrence->getStartTime();
        if ($recurrence->getStartTime()->format('w') > $currentDay->format('w')) {
            $startTime = new \DateTime("previous $firstDay {$recurrence->getStartTime()->format('c')}");
        }

        $fullWeeks = (ceil($recurrence->getOccurrences() / count($weekDays)) - 1) * $recurrence->getInterval();
        $afterFullWeeksDate = new \DateTime("+{$fullWeeks} week {$startTime->format('c')}");
        $fromStartInterval = $fullWeeks / $recurrence->getInterval() * count($weekDays);
        foreach ($weekDays as $day) {
            $currentDay = new \DateTime($day);
            if ($currentDay->format('w') < $recurrence->getStartTime()->format('w')) {
                $fromStartInterval = $fromStartInterval == 0 ? $fromStartInterval : $fromStartInterval - 1;
            }
        }

        if ($fromStartInterval + count($weekDays) < $recurrence->getOccurrences()) {
            $fullWeeks += $recurrence->getInterval();
            $afterFullWeeksDate = new \DateTime("+{$fullWeeks} week {$startTime->format('c')}");
            $fromStartInterval += count($weekDays);
        }

        foreach ($weekDays as $day) {
            $next = $this->getNextOccurrence($day, $afterFullWeeksDate);
            $fromStartInterval = $next >= $recurrence->getStartTime() ? $fromStartInterval + 1 : $fromStartInterval;
            if ($fromStartInterval >= $recurrence->getOccurrences()) {
                return $next;
            }
        }

        return $recurrence->getStartTime();
    }

    /**
     * {@inheritdoc}
     */
    public function getValidationErrorMessage(Recurrence $recurrence)
    {
        if (empty($recurrence->getDayOfWeek())) {
            return "dayOfWeek can't be empty for Weekly recurrence pattern";
        }

        return null;
    }
}
