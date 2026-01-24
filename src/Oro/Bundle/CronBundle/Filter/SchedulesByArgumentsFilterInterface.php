<?php

namespace Oro\Bundle\CronBundle\Filter;

use Oro\Bundle\CronBundle\Entity\Schedule;

/**
 * Defines the contract for filtering schedule entities by command arguments.
 *
 * Implementations of this interface filter collections of {@see Schedule} entities to find those
 * that match specific command arguments. This is essential for:
 * - Preventing duplicate schedule creation for the same command with the same arguments
 * - Finding existing schedules when updating or removing them
 * - Ensuring accurate schedule matching regardless of argument order
 *
 * The filtering typically uses argument hashing to provide order-independent comparison,
 * as the same command with some arguments should match a schedule with the same arguments.
 */
interface SchedulesByArgumentsFilterInterface
{
    /**
     * @param Schedule[] $schedules
     * @param string[]   $arguments
     *
     * @return Schedule[]
     */
    public function filter(array $schedules, array $arguments);
}
