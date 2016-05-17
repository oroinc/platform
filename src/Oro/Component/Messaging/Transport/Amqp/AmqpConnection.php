<?php
namespace Oro\Component\Messaging\Transport\Amqp;

use Oro\Component\Messaging\Transport\Connection;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class AmqpConnection implements Connection
{
    /**
     * @var AMQPStreamConnection
     */
    private $connection;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        if (false == defined('AMQP_WITHOUT_SIGNALS')) {
            define('AMQP_WITHOUT_SIGNALS', false);
        }
        if (true == AMQP_WITHOUT_SIGNALS) {
            throw new \LogicException('The AMQP_WITHOUT_SIGNALS must be set to false.');
        }
        
        $config = array_replace([
            'host' => 'localhost',
             'port' => 5672,
            'user' => null,
            'password' => null,
            'vhost' => '/',
        ], $config);


        $this->connection = new AMQPStreamConnection(
            $config['host'],
            $config['port'],
            $config['user'],
            $config['password'],
            $config['vhost']
        );
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
}
