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

    /**
     * @var array{string,array{string,MessageInterface}}
     */
    private array $sentMessages = [];

    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    public function createTransportMessage(): MessageInterface
    {
        return $this->driver->createTransportMessage();
    }

    public function send(QueueInterface $queue, Message $message): void
    {
        $this->sentMessages[$queue->getQueueName()][$message->getMessageId()] = [
            'topic' => $message->getProperty(Config::PARAMETER_TOPIC_NAME),
            'message' => $message,
        ];

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
        if ($queueName !== null) {
            return $this->sentMessages[$queueName];
        }

        return array_merge(...array_values($this->sentMessages));
    }

    public function clearTopicMessages(string $topic, ?string $queueName = null): void
    {
        $filteredSentMessages = [];

        foreach ($this->sentMessages as $eachQueueName => $messages) {
            if ($queueName !== $eachQueueName) {
                /** @var MessageInterface $message */
                foreach ($messages as $messageId => $message) {
                    if ($topic !== $message['topic']) {
                        $filteredSentMessages[$queueName][$messageId] = $message;
                    }
                }
            }
        }

        $this->sentMessages = $filteredSentMessages;
    }

    public function clear(?string $queueName = null): void
    {
        if ($queueName !== null) {
            $this->sentMessages[$queueName] = [];
        } else {
            $this->sentMessages = [];
        }
    }
}
