<?php
namespace Oro\Component\MessageQueue\Consumption;

use Oro\Component\MessageQueue\Transport\Amqp\AmqpQueue;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class ExposeMessageProcessor implements MessageProcessorInterface
{
    /**
     * @var string
     */
    protected $queueName;

    /**
     * @param string $queueName
     */
    public function __construct($queueName)
    {
        $this->queueName = $queueName;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        /** @var AmqpQueue $queue */
        $queue = $session->createQueue($this->queueName);
        $queue->setAutoDelete(false);
        $queue->setDurable(true);

        $session->declareQueue($queue);

        $session->createProducer()->send($queue, $message);
    }
}
