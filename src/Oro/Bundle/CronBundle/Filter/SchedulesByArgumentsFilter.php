<?php

namespace Oro\Bundle\CronBundle\Filter;

use Oro\Bundle\CronBundle\Entity\Schedule;

class SchedulesByArgumentsFilter implements SchedulesByArgumentsFilterInterface
{
    /**
     * @param Schedule[] $schedules
     * @param string[]   $arguments
     *
     * @return Schedule[]
     */
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
