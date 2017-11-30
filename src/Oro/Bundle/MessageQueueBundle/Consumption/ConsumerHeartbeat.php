<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption;

/**
 * Allows check for alive consumers and update of the consumers' alive date.
 */
class ConsumerHeartbeat
{
    /** @var StateDriverInterface */
    private $driver;

    /** @var int */
    private $updatePeriod;

    /**
     * @param StateDriverInterface $driver
     * @param int                  $updatePeriod
     */
    public function __construct(StateDriverInterface $driver, $updatePeriod)
    {
        $this->driver = $driver;
        $this->updatePeriod = $updatePeriod;
    }

    /**
     * Renews the date when consumer signals that it did not fail and continue to work normally.
     */
    public function tick()
    {
        $this->driver->setChangeStateDateWithTimeGap(
            new \DateTime('now', new \DateTimeZone('UTC'))
        );
    }

    /**
     * Checks if there are available consumers.
     *
     * @return bool
     */
    public function isAlive()
    {
        $lastAliveTime = $this->driver->getChangeStateDate();
        $currentTime = new \DateTime('now', new \DateTimeZone('UTC'));

        // return false if there is no correct state date, i.e. there is no consumer that has this date updated.
        if (!is_object($lastAliveTime)) {
            return false;
        }

        return !(($currentTime->getTimestamp() - $lastAliveTime->getTimestamp())/60 >= $this->updatePeriod);
    }
}
