<?php

namespace Oro\Component\MessageQueue\Client\Router;

use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Transport\QueueInterface;

/**
 * Contains message and queue for routing.
 */
class Envelope
{
    private QueueInterface $queue;

    private Message $message;

    public function __construct(QueueInterface $queue, Message $message)
    {
        $this->queue = $queue;
        $this->message = $message;
    }

    public function getQueue(): QueueInterface
    {
        return $this->queue;
    }

    public function getMessage(): Message
    {
        return $this->message;
    }
}
