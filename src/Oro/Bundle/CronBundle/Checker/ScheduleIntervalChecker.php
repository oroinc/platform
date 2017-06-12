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
     * @param null|\DateTime $dateTime
     *
     * @return bool
     */
    public function hasActiveSchedule(\Traversable $schedules, \DateTime $dateTime = null)
    {
        if (!$dateTime) {
            $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        }

        foreach ($schedules as $schedule) {
            if ($schedule instanceof ScheduleIntervalInterface
                && $this->isDateAfterScheduleStart($dateTime, $schedule)
                && $this->isDateBeforeScheduleEnd($dateTime, $schedule)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DateTime $dateTime
     * @param ScheduleIntervalInterface $schedule
     *
     * @return bool
     */
    private function isDateAfterScheduleStart(\DateTime $dateTime, ScheduleIntervalInterface $schedule)
    {
        return (!$schedule->getActiveAt() || $schedule->getActiveAt() <= $dateTime);
    }

    /**
     * @param \DateTime $dateTime
     * @param ScheduleIntervalInterface $schedule
     *
     * @return bool
     */
    private function isDateBeforeScheduleEnd(\DateTime $dateTime, ScheduleIntervalInterface $schedule)
    {
        return (!$schedule->getDeactivateAt() || $schedule->getDeactivateAt() > $dateTime);
    }
}
