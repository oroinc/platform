<?php
namespace Oro\Component\Messaging\ZeroConfig;

use \Oro\Component\Messaging\Transport\Amqp\AmqpSession as TransportAmqpSession;

class AmqpSession implements SessionInterface
{
    const DELIVERY_MODE_PERSISTENT = 2;

    /**
     * @var TransportAmqpSession
     */
    protected $session;

    /**
     * @var string
     */
    protected $routerTopicName;

    /**
     * @var string
     */
    protected $routerQueueName;

    /**
     * @var string
     */
    protected $queueTopicName;

    /**
     * {@inheritdoc}
     */
    public function createMessage()
    {
        return $this->session->createMessage(null, [], [
            'delivery_mode' => self::DELIVERY_MODE_PERSISTENT,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function createFrontProducer()
    {
        return new AmqpFrontProducer($this->session, $this->routerTopicName, $this->routerQueueName);
    }

    /**
     * {@inheritdoc}
     */
    public function createQueueProducer($queueName)
    {
        return new AmqpQueueProducer($this->session, $this->queueTopicName);
    }
}
