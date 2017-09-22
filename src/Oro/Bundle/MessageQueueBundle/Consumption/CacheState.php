<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption;

/**
 * Allows to update and get the date when cache was changed.
 */
class CacheState
{
    /** @var StateDriverInterface */
    private $driver;

    /**
     * @param StateDriverInterface $driver
     */
    public function __construct(StateDriverInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Renews the date when cache was changed.
     */
    public function renewChangeDate()
    {
        $this->driver->setChangeStateDate(new \DateTime('now', new \DateTimeZone('UTC')));
    }

    /**
     * Returns the date when cache was changed.
     *
     * @return \DateTime|null
     */
    public function getChangeDate()
    {
        return $this->driver->getChangeStateDate();
    }
}
