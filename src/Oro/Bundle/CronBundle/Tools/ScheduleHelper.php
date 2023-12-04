<?php

namespace Oro\Bundle\CronBundle\Tools;

use Oro\Bundle\CronBundle\Entity\ScheduleIntervalInterface;

/**
 * Provide info about active schedule for a given schedule collection.
 */
class ScheduleHelper
{
    /**
     * @param iterable|ScheduleIntervalInterface[] $schedules
     * @return bool
     */
    public static function hasActiveSchedule(iterable $schedules): bool
    {
        if (!$schedules) {
            return true;
        }

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        foreach ($schedules as $schedule) {
            if (!$schedule->getActiveAt() || $schedule->getActiveAt() <= $now) {
                if (!$schedule->getDeactivateAt() || $schedule->getDeactivateAt() > $now) {
                    // Active schedule found
                    return true;
                }
            }
        }

        return false;
    }
}
