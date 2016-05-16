<?php
namespace Oro\Component\Messaging\ZeroConfig;

use Oro\Component\Messaging\Transport\Amqp\AmqpQueue;
use Oro\Component\Messaging\Transport\Amqp\AmqpSession;
use Oro\Component\Messaging\Transport\Amqp\AmqpTopic;
use Oro\Component\Messaging\Transport\Message;

class AmqpQueueProducer implements ProducerInterface
{
    /**
     * @var AmqpSession
     */
    protected $session;

    /**
     * @var string
     */
    protected $topicName;

    /**
     * @param AmqpSession $session
     * @param string      $topicName
     */
    public function __construct(AmqpSession $session, $topicName)
    {
        $this->session = $session;
        $this->topicName = $topicName;
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

        $queueName = $message->getProperty('queueName');
        if (false == $queueName) {
            throw new \LogicException('Got message without "queueName" parameter');
        }

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
