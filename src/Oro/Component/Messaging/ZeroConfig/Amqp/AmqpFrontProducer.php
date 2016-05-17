<?php
namespace Oro\Component\Messaging\ZeroConfig\Amqp;

use Oro\Component\Messaging\Transport\Amqp\AmqpQueue;
use Oro\Component\Messaging\Transport\Amqp\AmqpSession as TransportAmqpSession;
use Oro\Component\Messaging\Transport\Amqp\AmqpTopic;
use Oro\Component\Messaging\Transport\Message;
use Oro\Component\Messaging\ZeroConfig\ProducerInterface;

class AmqpFrontProducer implements ProducerInterface
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
    protected $queueName;

    /**
     * @param TransportAmqpSession $session
     * @param string               $topicName
     * @param string               $queueName
     */
    public function __construct(TransportAmqpSession $session, $topicName, $queueName)
    {
        $this->session = $session;
        $this->topicName = $topicName;
        $this->queueName = $queueName;
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
        $topic = $this->session->createTopic($this->topicName);
        $topic->setType('fanout');
        $topic->setDurable(true);

        return $topic;
    }

    /**
     * @return AmqpQueue
     */
    protected function createQueue()
    {
        $queue = $this->session->createQueue($this->queueName);
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
