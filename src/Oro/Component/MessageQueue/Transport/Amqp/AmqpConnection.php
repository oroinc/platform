<?php
namespace Oro\Component\MessageQueue\Transport\Amqp;

use Oro\Component\MessageQueue\Transport\Connection;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class AmqpConnection implements Connection
{
    /**
     * @var AbstractConnection
     */
    private $connection;

    /**
     * @param AbstractConnection $connection
     */
    public function __construct(AbstractConnection $connection)
    {
        if (false == defined('AMQP_WITHOUT_SIGNALS')) {
            define('AMQP_WITHOUT_SIGNALS', false);
        }
        if (true == AMQP_WITHOUT_SIGNALS) {
            throw new \LogicException('The AMQP_WITHOUT_SIGNALS must be set to false.');
        }

        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     *
     * @return AmqpSession
     */
    public function createSession()
    {
        return new AmqpSession($this->connection->channel());
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->connection->close();
    }

    /**
     * @param array $config
     *
     * @return static
     */
    public static function createFromConfig(array $config)
    {
        $config = array_replace([
            'host' => 'localhost',
            'port' => 5672,
            'user' => null,
            'password' => null,
            'vhost' => '/',
        ], $config);

        return new static(new AMQPStreamConnection(
            $config['host'],
            $config['port'],
            $config['user'],
            $config['password'],
            $config['vhost']
        ));
    }
}
