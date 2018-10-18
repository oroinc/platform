<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Filter;

use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\CronBundle\Filter\SchedulesByArgumentsFilter;

class SchedulesByArgumentsFilterTest extends \PHPUnit\Framework\TestCase
{
    public function testFilter()
    {
        $schedules = [
            $this->createSchedule('oro:test', [], '* * * * *'),
            $this->createSchedule('oro:test', ['arg1', 'arg2'], '* * * * *'),
            $this->createSchedule('oro:test', ['arg1', 'arg3'], '* * * * *'),
        ];
        $arguments = ['arg1', 'arg2'];

        $filter = new SchedulesByArgumentsFilter();

        static::assertSame(
            [1 => $schedules[1]],
            $filter->filter($schedules, $arguments)
        );
    }

    /**
     * @param string $command
     * @param array  $arguments
     * @param string $definition
     *
     * @return Schedule
     */
    private function createSchedule($command, array $arguments, $definition)
    {
        $schedule = new Schedule();
        $schedule
            ->setCommand($command)
            ->setArguments($arguments)
            ->setDefinition($definition);

        return $schedule;
    }
}
