<?php

namespace Oro\Component\MessageQueue\Client;

use Oro\Component\MessageQueue\Transport\ConnectionInterface;

/**
 * Provides an interface of a factory that creates a message queue driver.
 */
interface DriverFactoryInterface
{
    /**
     * Creates a new instance of a message queue driver.
     *
     * @param ConnectionInterface $connection
     * @param Config     $config
     *
     * @return DriverInterface
     */
    public function create(ConnectionInterface $connection, Config $config);
}
