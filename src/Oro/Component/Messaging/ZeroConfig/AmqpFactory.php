<?php
namespace Oro\Component\Messaging\ZeroConfig;

use Oro\Component\Messaging\Transport\Amqp\AmqpSession;
use Oro\Component\Messaging\Transport\MessageProducer;
use Oro\Component\Messaging\Transport\Queue;

class AmqpFactory
{
    const DELIVERY_MODE_PERSISTENT = 2;

    /**
     * @var AmqpSession
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
    protected $consumerTopicName;
    
    /**
     * @param AmqpSession $session
     * @param string      $routerTopicName
     * @param string      $routerQueueName
     */
    public function __construct(AmqpSession $session, $routerTopicName, $routerQueueName)
    {
        $this->session = $session;
        $this->routerTopicName = $routerTopicName;
        $this->routerQueueName = $routerQueueName;
    }

    /**
     * {@inheritdoc}
     */
    public function createRouterMessage($messageName, $messageBody)
    {
        $properties = [
            'messageName' => $messageName,
        ];

        $headers = [
            'delivery_mode' => self::DELIVERY_MODE_PERSISTENT,
        ];

        return $this->session->createMessage($messageBody, $properties, $headers);
    }

    /**
     * {@inheritdoc}
     */
    public function createRouterTopic()
    {
        $topic = $this->session->createTopic($this->routerTopicName);
        $topic->setType('fanout');
        $topic->setDurable(true);

        return $topic;
    }

    /**
     * {@inheritdoc}
     */
    public function createRouterQueue()
    {
        $queue = $this->session->createQueue($this->routerQueueName);
        $queue->setDurable(true);
        $queue->setAutoDelete(false);

        return $queue;
    }

    /**
     * {@inheritdoc}
     */
    public function createRouterMessageProducer()
    {
        $this->createRouterSchema();

        return $this->session->createProducer($this->createRouterTopic());
    }

    /**
     * {@inheritdoc}
     */
    public function createConsumerMessage($messageName, $handlerName, $messageBody)
    {
        $properties = [
            'messageName' => $messageName,
            'handlerName' => $handlerName,
        ];

        $headers = [
            'delivery_mode' => self::DELIVERY_MODE_PERSISTENT,
        ];

        return $this->session->createMessage($messageBody, $properties, $headers);
    }

    /**
     * {@inheritdoc}
     */
    public function createConsumerTopic($consumerName)
    {
        $topic = $this->session->createTopic($this->consumerTopicName);
        $topic->setType('direct');
        $topic->setDurable(true);
        $topic->setRoutingKey($consumerName);

        return $topic;
    }

    /**
     * {@inheritdoc}
     */
    public function createConsumerQueue($consumerName)
    {
        $queue = $this->session->createQueue($consumerName);
        $queue->setDurable(true);
        $queue->setAutoDelete(false);
    }

    /**
     * {@inheritdoc}
     */
    public function createConsumerMessageProducer($consumerName)
    {
        $this->createConsumerSchema($consumerName);

        return $this->session->createProducer($this->createConsumerTopic($consumerName));
    }

    protected function createRouterSchema()
    {
        $topic = $this->createRouterTopic();
        $queue = $this->createRouterQueue();

        $this->session->declareTopic($topic);
        $this->session->declareQueue($queue);
        $this->session->declareBind($topic, $queue);
    }

    /**
     * @param string $consumerName
     */
    protected function createConsumerSchema($consumerName)
    {
        $topic = $this->createConsumerTopic($consumerName);
        $queue = $this->createConsumerQueue($consumerName);

        $this->session->declareTopic($topic);
        $this->session->declareQueue($queue);
        $this->session->declareBind($topic, $queue);
    }
}
