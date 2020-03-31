<?php

namespace Oro\Component\MessageQueue\Client;

use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\QueueInterface;

/**
 * Provides an interface of message queue driver.
 * Gives possibility to create transport message, create queue and send queue to message broker.
 */
interface DriverInterface
{
    /**
     * @return MessageInterface
     */
    public function createTransportMessage(): MessageInterface;

    /**
     * @param QueueInterface $queue
     * @param Message $message
     */
    public function send(QueueInterface $queue, Message $message): void;

    /**
     * @param string $queueName
     *
     * @return QueueInterface
     */
    public function createQueue(string $queueName): QueueInterface;

    /**
     * @return Config
     */
    public function getConfig(): Config;
}
