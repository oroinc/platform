<?php
namespace Oro\Component\Messaging\ZeroConfig;

use Oro\Component\Messaging\Transport\Amqp\AmqpConnection;
use Oro\Component\Messaging\Transport\Connection;
use Oro\Component\Messaging\Transport\Null\NullConnection;

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
