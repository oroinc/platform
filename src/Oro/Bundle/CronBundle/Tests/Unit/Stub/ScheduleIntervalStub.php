<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Stub;

use Oro\Bundle\CronBundle\Entity\ScheduleIntervalInterface;
use Oro\Bundle\CronBundle\Entity\ScheduleIntervalsAwareInterface;
use Oro\Bundle\CronBundle\Entity\ScheduleIntervalTrait;

class ScheduleIntervalStub implements ScheduleIntervalInterface
{
    use ScheduleIntervalTrait;

    /**
     * @var ScheduleIntervalsAwareInterface
     */
    private $holder;

    /**
     * {@inheritdoc}
     */
    public function getScheduleIntervalsHolder()
    {
        return $this->holder;
    }

    /**
     * @param ScheduleIntervalsAwareInterface $holder
     * @return $this
     */
    public function setHolder(ScheduleIntervalsAwareInterface $holder)
    {
        $this->holder = $holder;

        return $this;
    }
}
