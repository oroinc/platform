<?php

namespace Oro\Component\MessageQueue\Client\Router;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;
use Oro\Component\MessageQueue\Client\Meta\TopicMetaRegistry;

/**
 * Knows about the list of queues associated with the topic, returns the list of recipients.
 */
class MessageRouter implements MessageRouterInterface
{
    private DriverInterface $driver;

    private TopicMetaRegistry $topicMetaRegistry;

    private DestinationMetaRegistry $destinationMetaRegistry;

    public function __construct(
        DriverInterface $driver,
        TopicMetaRegistry $topicMetaRegistry,
        DestinationMetaRegistry $destinationMetaRegistry
    ) {
        $this->driver = $driver;
        $this->destinationMetaRegistry = $destinationMetaRegistry;
        $this->topicMetaRegistry = $topicMetaRegistry;
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

        $topicMeta = $this->topicMetaRegistry->getTopicMeta($topicName);
        foreach ($topicMeta->getQueueNames() as $queueName) {
            $transportQueueName = $this->destinationMetaRegistry
                ->getDestinationMeta($queueName)
                ->getTransportQueueName();

            yield $this->createEnvelope($message, $transportQueueName);
        }
    }

    private function createEnvelope(Message $message, string $transportQueueName): Envelope
    {
        $newMessage = clone $message;
        $newMessage->setProperty(Config::PARAMETER_QUEUE_NAME, $transportQueueName);

        $queue = $this->driver->createQueue($transportQueueName);

        return new Envelope($queue, $newMessage);
    }
}
