<?php
namespace Oro\Component\MessageQueue\ZeroConfig;

use Oro\Component\MessageQueue\Transport\Amqp\AmqpConnection;
use Oro\Component\MessageQueue\Transport\Connection;
use Oro\Component\MessageQueue\Transport\Null\NullConnection;

class SessionFactory
{
    /**
     * @param Connection $connection
     * @param Config     $config
     *
     * @return Session
     */
    public static function create(Connection $connection, Config $config)
    {
        if ($connection instanceof  AmqpConnection) {
            return new AmqpSession($connection->createSession(), $config);
        } elseif ($connection instanceof NullConnection) {
            return new NullSession($connection->createSession(), $config);
        } else {
            throw new \LogicException(sprintf('Unexpected connection instance: "%s"', get_class($connection)));
        }
    }
}
