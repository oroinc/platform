<?php

namespace Oro\Bundle\MessageQueueBundle\Test\Functional;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\QueueInterface;

/**
 * This class is intended to be used in functional tests and allows to get sent messages.
 */
class DriverMessageCollector implements DriverInterface
{
    private DriverInterface $driver;

    private SentMessagesStorage $sentMessagesStorage;

    public function __construct(DriverInterface $driver, SentMessagesStorage $sentMessagesStorage)
    {
        $this->driver = $driver;
        $this->sentMessagesStorage = $sentMessagesStorage;
    }

    public function createTransportMessage(): MessageInterface
    {
        return $this->driver->createTransportMessage();
    }

    public function send(QueueInterface $queue, Message $message): void
    {
        $this->sentMessagesStorage->addSentMessage($queue, $message);

        $this->driver->send($queue, $message);
    }

    public function createQueue(string $queueName): QueueInterface
    {
        return $this->driver->createQueue($queueName);
    }

    public function getConfig(): Config
    {
        return $this->driver->getConfig();
    }

    /**
     * @return array<string,array{string,MessageInterface}>
     */
    public function getSentMessages(?string $queueName = null): array
    {
        return $this->sentMessagesStorage->getSentMessages($queueName);
    }

    public function clearTopicMessages(string $topic, ?string $queueName = null): void
    {
        $this->sentMessagesStorage->clearTopicMessages($topic, $queueName);
    }

    public function clear(?string $queueName = null): void
    {
        $this->sentMessagesStorage->clear($queueName);
    }
}
