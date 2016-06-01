<?php
namespace Oro\Component\MessageQueue\Client;

use Oro\Component\MessageQueue\Transport\Amqp\AmqpConnection;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use Oro\Component\MessageQueue\Transport\Null\NullConnection;

class DriverFactory
{
    /**
     * @param ConnectionInterface $connection
     * @param Config     $config
     *
     * @return DriverInterface
     */
    public static function create(ConnectionInterface $connection, Config $config)
    {
        if ($connection instanceof  AmqpConnection) {
            return new AmqpDriver($connection->createSession(), $config);
        } elseif ($connection instanceof NullConnection) {
            return new NullDriver($connection->createSession(), $config);
        } else {
            throw new \LogicException(sprintf('Unexpected connection instance: "%s"', get_class($connection)));
        }
    }
}
