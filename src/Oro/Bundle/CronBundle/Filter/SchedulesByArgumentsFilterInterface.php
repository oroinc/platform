<?php

namespace Oro\Bundle\CronBundle\Filter;

use Oro\Bundle\CronBundle\Entity\Schedule;

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
