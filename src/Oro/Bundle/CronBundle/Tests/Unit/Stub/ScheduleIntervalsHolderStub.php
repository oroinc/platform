<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Stub;

use Oro\Bundle\CronBundle\Entity\ScheduleIntervalsAwareInterface;

class ScheduleIntervalsHolderStub implements ScheduleIntervalsAwareInterface
{
    /**
     * @var array|\Traversable
     */
    private $schedules = [];

    /**
     * {@inheritdoc}
     */
    public function getSchedules()
    {
        return $this->schedules;
    }

    /**
     * @param array|\Traversable $schedules
     * @return $this
     */
    public function setSchedules($schedules)
    {
        $this->schedules = $schedules;

        return $this;
    }
}
