<?php
namespace Oro\Component\Messaging\ZeroConfig\Amqp;

use Oro\Component\Messaging\Transport\Amqp\AmqpQueue;
use Oro\Component\Messaging\Transport\Amqp\AmqpSession as TransportAmqpSession;
use Oro\Component\Messaging\Transport\Amqp\AmqpTopic;
use Oro\Component\Messaging\Transport\Message;
use Oro\Component\Messaging\ZeroConfig\Config;
use Oro\Component\Messaging\ZeroConfig\ProducerInterface;

class AmqpFrontProducer implements ProducerInterface
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
        $messageName = $message->getProperty('messageName');
        if (false == $messageName) {
            throw new \LogicException('Got message without "messageName" parameter');
        }
        
        $topic = $this->createTopic();
        $queue = $this->createQueue();

        $this->createSchema($topic, $queue);

        $producer = $this->session->createProducer($topic);
        $producer->send($topic, $message);
    }

    /**
     * @return AmqpTopic
     */
    protected function createTopic()
    {
        $topic = $this->session->createTopic($this->config->getRouterTopicName());
        $topic->setType('fanout');
        $topic->setDurable(true);

        return $topic;
    }

    /**
     * @return AmqpQueue
     */
    protected function createQueue()
    {
        $queue = $this->session->createQueue($this->config->getRouterQueueName());
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
