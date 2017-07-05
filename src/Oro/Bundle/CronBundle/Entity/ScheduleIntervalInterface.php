<?php

namespace Oro\Bundle\CronBundle\Entity;

/**
 * This interface is for entities which contain schedule interval data in it.
 */
interface ScheduleIntervalInterface
{
    /**
     * @return \DateTime|null
     */
    public function getActiveAt();

    /**
     * @param \DateTime|null $activeAt
     * @return $this
     */
    public function setActiveAt(\DateTime $activeAt = null);

    /**
     * @return \DateTime|null
     */
    public function getDeactivateAt();

    /**
     * @param \DateTime|null $deactivateAt
     * @return $this
     */
    public function setDeactivateAt(\DateTime $deactivateAt = null);

    /**
     * @return ScheduleIntervalsAwareInterface
     */
    public function getScheduleIntervalsHolder();
}
