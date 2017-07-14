<?php

namespace Oro\Bundle\CronBundle\Entity;

/**
 * This trait is intended to be used for entities which implement ScheduleIntervalInterface.
 */
trait ScheduleIntervalTrait
{
    /**
     * @var \DateTime|null
     */
    protected $activeAt;

    /**
     * @var \DateTime|null
     */
    protected $deactivateAt;

    /**
     * @return \DateTime|null
     */
    public function getActiveAt()
    {
        return $this->activeAt;
    }

    /**
     * @param \DateTime|null $activeAt
     * @return $this
     */
    public function setActiveAt(\DateTime $activeAt = null)
    {
        $this->activeAt = $activeAt;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getDeactivateAt()
    {
        return $this->deactivateAt;
    }

    /**
     * @param \DateTime|null $deactivateAt
     * @return $this
     */
    public function setDeactivateAt(\DateTime $deactivateAt = null)
    {
        $this->deactivateAt = $deactivateAt;

        return $this;
    }
}
