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
    public function createTransportMessage(): MessageInterface;

    public function send(QueueInterface $queue, Message $message): void;

    public function createQueue(string $queueName): QueueInterface;

    public function getConfig(): Config;
}
