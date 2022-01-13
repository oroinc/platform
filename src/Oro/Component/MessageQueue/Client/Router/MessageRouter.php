<?php

namespace Oro\Component\MessageQueue\Client\Router;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;
use Oro\Component\MessageQueue\Client\Meta\TopicMetaRegistry;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Oro\Component\MessageQueue\Topic\TopicRegistry;

/**
 * Knows about the list of queues associated with the topic, returns the list of recipients.
 */
class MessageRouter implements MessageRouterInterface
{
    private DriverInterface $driver;

    private TopicRegistry $topicRegistry;

    private TopicMetaRegistry $topicMetaRegistry;

    private DestinationMetaRegistry $destinationMetaRegistry;

    public function __construct(
        DriverInterface $driver,
        TopicRegistry $topicRegistry,
        TopicMetaRegistry $topicMetaRegistry,
        DestinationMetaRegistry $destinationMetaRegistry
    ) {
        $this->driver = $driver;
        $this->topicRegistry = $topicRegistry;
        $this->topicMetaRegistry = $topicMetaRegistry;
        $this->destinationMetaRegistry = $destinationMetaRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Message $message): iterable
    {
        $topicName = $message->getProperty(Config::PARAMETER_TOPIC_NAME);
        if (!$topicName) {
            throw new \LogicException(
                sprintf('Property "%s" was expected to be defined in a message', Config::PARAMETER_TOPIC_NAME)
            );
        }

        $topic = $this->topicRegistry->get($topicName);
        $topicMeta = $this->topicMetaRegistry->getTopicMeta($topicName);
        foreach ($topicMeta->getQueueNames() as $queueName) {
            yield $this->createEnvelope($message, $topic, $queueName);
        }
    }

    private function createEnvelope(Message $message, TopicInterface $topic, string $queueName): Envelope
    {
        $newMessage = clone $message;

        if (!$message->getPriority()) {
            $newMessage->setPriority($topic->getDefaultPriority($queueName));
        }

        $transportQueueName = $this->destinationMetaRegistry
            ->getDestinationMeta($queueName)
            ->getTransportQueueName();

        $newMessage->setProperty(Config::PARAMETER_QUEUE_NAME, $transportQueueName);

        $queue = $this->driver->createQueue($transportQueueName);

        return new Envelope($queue, $newMessage);
    }
}
