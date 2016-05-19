<?php
namespace Oro\Component\Messaging\ZeroConfig;

use Oro\Component\Messaging\Transport\Amqp\AmqpConnection;
use Oro\Component\Messaging\Transport\Amqp\AmqpMessage;

class AmqpSession implements Session
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param AmqpConnection $connection
     * @param Config $config
     */
    public function __construct(AmqpConnection $connection, Config $config)
    {
        $this->session = $connection->createSession();
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
    public function createFrontProducer()
    {
        return new FrontProducer($this, $this->config);
    }

    /**
     * {@inheritdoc}
     */
    public function createQueueProducer()
    {
        return new QueueProducer($this, $this->config);
    }

    /**
     * {@inheritdoc}
     */
    public function createFrontTopic()
    {
        $topic = $this->session->createTopic($this->config->getRouterTopicName());
        $topic->setType('fanout');
        $topic->setDurable(true);

        return $topic;
    }

    /**
     * {@inheritdoc}
     */
    public function createFrontQueue()
    {
        $queue = $this->session->createQueue($this->config->getRouterQueueName());
        $queue->setDurable(true);
        $queue->setAutoDelete(false);

        return $queue;
    }

    /**
     * {@inheritdoc}
     */
    public function createQueueTopic($queueName)
    {
        $topic = $this->session->createTopic($this->config->getQueueTopicName());
        $topic->setType('direct');
        $topic->setDurable(true);
        $topic->setRoutingKey($queueName);

        return $topic;
    }

    /**
     * {@inheritdoc}
     */
    public function createQueueQueue($queueName)
    {
        $queue = $this->session->createQueue($queueName);
        $queue->setDurable(true);
        $queue->setAutoDelete(false);

        return $queue;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Oro\Component\Messaging\Transport\Amqp\AmqpSession
     */
    public function getTransportSession()
    {
        return $this->session;
    }
}