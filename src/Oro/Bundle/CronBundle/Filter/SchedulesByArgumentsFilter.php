<?php

namespace Oro\Bundle\CronBundle\Filter;

use Oro\Bundle\CronBundle\Entity\Schedule;

/**
 * Filters schedule entities by matching command arguments using hash comparison.
 *
 * This filter compares schedules based on their argument hashes rather than direct array comparison,
 * which provides several benefits:
 * - Order-independent matching (arguments are sorted before hashing)
 * - Efficient comparison using MD5 hashes
 * - Consistent behavior with how Schedule entities store and compare arguments
 *
 * The filtering process creates a temporary {@see Schedule} entity with the provided arguments,
 * generates its hash, and then filters the input schedules to only those with matching hashes.
 * This ensures that schedules are matched correctly even when arguments are specified in
 * different orders.
 */
class SchedulesByArgumentsFilter implements SchedulesByArgumentsFilterInterface
{
    /**
     * @param Schedule[] $schedules
     * @param string[]   $arguments
     *
     * @return Schedule[]
     */
    #[\Override]
    public function filter(array $schedules, array $arguments)
    {
        $argsSchedule = new Schedule();
        $argsSchedule->setArguments($arguments);

        return array_filter(
            $schedules,
            function (Schedule $schedule) use ($argsSchedule) {
                return $schedule->getArgumentsHash() === $argsSchedule->getArgumentsHash();
            }
        );
    }
}
