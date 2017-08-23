<?php

namespace Oro\Bundle\CronBundle\Entity;

interface ScheduleIntervalsAwareInterface
{
    /**
     * @return ScheduleIntervalInterface[]
     */
    public function getSchedules();
}
