<?php
namespace Oro\Component\Messaging\ZeroConfig\Amqp;

use Oro\Component\Messaging\Transport\Amqp\AmqpQueue;
use Oro\Component\Messaging\Transport\Amqp\AmqpSession as TransportAmqpSession;
use Oro\Component\Messaging\Transport\Amqp\AmqpTopic;
use Oro\Component\Messaging\Transport\Message;
use Oro\Component\Messaging\ZeroConfig\ProducerInterface;

class AmqpQueueProducer implements ProducerInterface
{
    /**
     * @var TransportAmqpSession
     */
    protected $session;

    /**
     * @var string
     */
    protected $topicName;

    /**
     * @var string
     */
    protected $defaultQueueName;

    /**
     * @param TransportAmqpSession $session
     * @param string               $topicName
     * @param string               $defaultQueueName
     */
    public function __construct(TransportAmqpSession $session, $topicName, $defaultQueueName)
    {
        $this->session = $session;
        $this->topicName = $topicName;
        $this->defaultQueueName = $defaultQueueName;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Message $message)
    {
        $processorName = $message->getProperty('processorName');
        if (false == $processorName) {
            throw new \LogicException('Got message without "processorName" parameter');
        }

        $queueName = $message->getProperty('queueName') ?: $this->defaultQueueName;

        $topic = $this->createTopic($this->generateRoutingKey($queueName));
        $queue = $this->createQueue($this->generateQueueName($queueName));

        $this->createSchema($topic, $queue);

        $producer = $this->session->createProducer($topic);
        $producer->send($topic, $message);
    }

    /**
     * @param string $routingKey
     *
     * @return AmqpTopic
     */
    protected function createTopic($routingKey)
    {
        $topic = $this->session->createTopic($this->topicName);
        $topic->setType('direct');
        $topic->setDurable(true);
        $topic->setRoutingKey($routingKey);

        return $topic;
    }

    /**
     * @param string $queueName
     *
     * @return AmqpQueue
     */
    protected function createQueue($queueName)
    {
        $queue = $this->session->createQueue($queueName);
        $queue->setDurable(true);
        $queue->setAutoDelete(false);

        return $queue;
    }

    /**
     * @param AmqpTopic $topic
     * @param AmqpQueue $queue
     */
    protected function createSchema(AmqpTopic $topic, AmqpQueue $queue)
    {
        $this->session->declareTopic($topic);
        $this->session->declareQueue($queue);
        $this->session->declareBind($topic, $queue);
    }

    /**
     * @param string $queueName
     *
     * @return string
     */
    protected function generateRoutingKey($queueName)
    {
        return strtolower($queueName);
    }

    /**
     * @param string $queueName
     *
     * @return string
     */
    protected function generateQueueName($queueName)
    {
        return strtolower($queueName);
    }
}
