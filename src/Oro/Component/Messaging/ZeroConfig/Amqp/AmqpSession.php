<?php
namespace Oro\Component\Messaging\ZeroConfig\Amqp;

use Oro\Component\Messaging\Transport\Amqp\AmqpMessage;
use \Oro\Component\Messaging\Transport\Amqp\AmqpSession as TransportAmqpSession;
use Oro\Component\Messaging\ZeroConfig\SessionInterface;

class AmqpSession implements SessionInterface
{
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
     * @var string
     */
    protected $defaultQueueQueueName;

    /**
     * @param TransportAmqpSession $session
     * @param string               $routerTopicName
     * @param string               $routerQueueName
     * @param string               $queueTopicName
     */
    public function __construct(TransportAmqpSession $session, $routerTopicName, $routerQueueName, $queueTopicName)
    {
        $this->session = $session;
        $this->routerTopicName = $routerTopicName;
        $this->routerQueueName = $routerQueueName;
        $this->queueTopicName = $queueTopicName;
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
        return new AmqpFrontProducer($this->session, $this->routerTopicName, $this->routerQueueName);
    }

    /**
     * {@inheritdoc}
     */
    public function createQueueProducer()
    {
        return new AmqpQueueProducer($this->session, $this->queueTopicName, $this->defaultQueueQueueName);
    }
}
