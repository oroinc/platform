<?php

namespace Oro\Bundle\CalendarBundle\Strategy\Recurrence;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;

class WeeklyStrategy implements StrategyInterface
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
        $weekDays = $recurrence->getDayOfWeek();

        if (empty($weekDays)) {
            return $result;
        }
        // @TODO extract validation into abstract class or strategy helper.
        if (false === filter_var($recurrence->getInterval(), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
            throw new \RuntimeException('Value should be integer with min_rage >= 1.');
        }

        //week days should be sorted in standard sequence (sun, mon, tue...)
        usort($weekDays, function ($item1, $item2) {
            $date1 = new \DateTime($item1);
            $date2 = new \DateTime($item2);

            if ($date1->format('w') === $date2->format('w')) {
                return 0;
            }

            return $date1->format('w') > $date2->format('w');
        });

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
        return 'weekly';
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
}
