<?php

namespace Oro\Bundle\IntegrationBundle\EventListener;

use Oro\Bundle\IntegrationBundle\Event\SyncEvent;
use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Transport\TransactionInterface;

class IntegrationSyncListener
{
    /** @var DriverInterface */
    private $driver;

    /**
     * @param DriverInterface $driver
     */
    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * If message queue driver is instance of \Oro\Component\MessageQueue\Transport\TransactionInterface
     * open transaction before import job was executed.
     * Fix error when consumers trying to process not committed job processes.
     *
     * @param SyncEvent $event
     */
    public function syncBefore(SyncEvent $event)
    {
        if ($this->driver instanceof TransactionInterface) {
            $this->driver->startTransaction();
        }
    }

    /**
     * If message queue driver is instance of \Oro\Component\MessageQueue\Transport\TransactionInterface
     * commit transaction after import job was executed.
     * Fix error when consumers trying to process not committed job processes.
     *
     * @param SyncEvent $event
     */
    public function syncAfter(SyncEvent $event)
    {
        if ($this->driver instanceof TransactionInterface) {
            $this->driver->commit();
        }
    }
}
