<?php

namespace Oro\Bundle\CronBundle\Checker;

use Oro\Bundle\CronBundle\Entity\ScheduleIntervalInterface;

/**
 * Service that helps checking that inside collection of schedules exists active one.
 */
class ScheduleIntervalChecker
{
    /**
     * @param ScheduleIntervalInterface[]|\Traversable $schedules
     * @param null|\DateTime $date
     *
     * @return bool
     */
    public function hasActiveSchedule(\Traversable $schedules, $date = null)
    {
        if (!$date) {
            $date = new \DateTime('now', new \DateTimeZone('UTC'));
        }

        foreach ($schedules as $schedule) {
            if ($schedule instanceof ScheduleIntervalInterface
                && $this->isDateAfterScheduleStart($date, $schedule)
                && $this->isDateBeforeScheduleEnd($date, $schedule)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DateTime $date
     * @param ScheduleIntervalInterface $schedule
     *
     * @return bool
     */
    private function isDateAfterScheduleStart(\DateTime $date, ScheduleIntervalInterface $schedule)
    {
        return (!$schedule->getActiveAt() || $schedule->getActiveAt() < $date);
    }

    /**
     * @param \DateTime $date
     * @param ScheduleIntervalInterface $schedule
     *
     * @return bool
     */
    private function isDateBeforeScheduleEnd(\DateTime $date, ScheduleIntervalInterface $schedule)
    {
        return (!$schedule->getDeactivateAt() || $schedule->getDeactivateAt() > $date);
    }
}
