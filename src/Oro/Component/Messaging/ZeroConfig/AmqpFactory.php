<?php
namespace Oro\Component\Messaging\ZeroConfig;

use Oro\Component\Messaging\Transport\Amqp\AmqpSession;

class AmqpFactory implements FactoryInterface
{
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
        return $this->session->createMessage($messageBody, [
            'messageName' => $messageName,
        ]);
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
    protected function createRouterSchema()
    {
        $topic = $this->createRouterTopic();
        $queue = $this->createRouterQueue();

        $this->session->declareTopic($topic);
        $this->session->declareQueue($queue);
        $this->session->declareBind($topic, $queue);
    }
}
