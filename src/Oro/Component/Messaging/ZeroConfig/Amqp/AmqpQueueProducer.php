<?php
namespace Oro\Component\Messaging\ZeroConfig\Amqp;

use Oro\Component\Messaging\Transport\Amqp\AmqpQueue;
use Oro\Component\Messaging\Transport\Amqp\AmqpSession as TransportAmqpSession;
use Oro\Component\Messaging\Transport\Amqp\AmqpTopic;
use Oro\Component\Messaging\Transport\Message;
use Oro\Component\Messaging\ZeroConfig\Config;
use Oro\Component\Messaging\ZeroConfig\ProducerInterface;

class AmqpQueueProducer implements ProducerInterface
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
    public function send(Message $message)
    {
        $processorName = $message->getProperty('processorName');
        if (false == $processorName) {
            throw new \LogicException('Got message without "processorName" parameter');
        }

        $queueName = $message->getProperty('queueName')
            ? $this->config->formatName($message->getProperty('queueName'))
            : $this->config->getDefaultQueueQueueName()
        ;

        $topic = $this->createTopic($queueName);
        $queue = $this->createQueue($queueName);

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
        $topic = $this->session->createTopic($this->config->getQueueTopicName());
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
}
