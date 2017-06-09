<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Stub;

use Oro\Bundle\CronBundle\Entity\ScheduleIntervalInterface;
use Oro\Bundle\CronBundle\Entity\ScheduleIntervalTrait;

class ScheduleIntervalStub implements ScheduleIntervalInterface
{
    use ScheduleIntervalTrait;
}
