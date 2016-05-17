<?php
namespace Oro\Component\Messaging\Transport\Amqp;

use Oro\Component\Messaging\Transport\Connection;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class AmqpConnection implements Connection
{
    private $connection;
    
    public function __construct(array $config)
    {
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
