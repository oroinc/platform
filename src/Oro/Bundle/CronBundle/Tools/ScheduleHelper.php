<?php

namespace Oro\Bundle\CronBundle\Tools;

/**
 * Provide info about active schedule for a given schedule collection.
 */
class ScheduleHelper
{
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
