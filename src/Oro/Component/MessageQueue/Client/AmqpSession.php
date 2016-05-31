<?php
namespace Oro\Component\MessageQueue\Client;

use Oro\Component\MessageQueue\Transport\Amqp\AmqpMessage;
use Oro\Component\MessageQueue\Transport\Amqp\AmqpSession as TransportAmqpSession;
use Oro\Component\MessageQueue\Transport\QueueInterface;

class AmqpSession implements SessionInterface
{
    /**
     * @var TransportAmqpSession
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
        return new MessageProducer($this->session->createProducer(), $this);
    }
    
    /**
     * @param string $queueName
     *
     * @return QueueInterface
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
