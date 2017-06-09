<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Checker\Stub;

use Oro\Bundle\CronBundle\Entity\ScheduleIntervalInterface;
use Oro\Bundle\CronBundle\Entity\ScheduleIntervalTrait;

/**
 * {@inheritDoc}
 */
class ScheduleIntervalStub implements ScheduleIntervalInterface
{
    use ScheduleIntervalTrait;
}
