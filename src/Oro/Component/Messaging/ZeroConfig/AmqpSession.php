<?php
namespace Oro\Component\Messaging\ZeroConfig;

use Oro\Component\Messaging\Transport\Amqp\AmqpMessage;
use Oro\Component\Messaging\Transport\Amqp\AmqpSession as TransportAmqpSession;
use Oro\Component\Messaging\Transport\Queue;

class AmqpSession implements Session
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param TransportAmqpSession $session
     * @param Config               $config
     */
    public function __construct(TransportAmqpSession $session, Config $config)
    {
        $this->session = $session;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function createMessage()
    {
        return $this->session->createMessage(null, [], [
            'delivery_mode' => AmqpMessage::DELIVERY_MODE_PERSISTENT,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function createProducer()
    {
        return $this->session->createProducer();
    }

    /**
     * {@inheritdoc}
     */
    public function createFrontProducer()
    {
        return new FrontProducer($this, $this->config);
    }

    /**
     * @param string $queueName
     *
     * @return Queue
     */
    public function createQueue($queueName)
    {
        $queue = $this->session->createQueue($queueName);
        $queue->setDurable(true);
        $queue->setAutoDelete(false);
        $this->session->declareQueue($queue);

        return $queue;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }
}