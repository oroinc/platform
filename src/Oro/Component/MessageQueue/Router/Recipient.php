<?php

namespace Oro\Component\MessageQueue\Router;

use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\QueueInterface;

/**
 * Class Recipient that contains message and queue for routing.
 */
class Recipient
{
    /**
     * @var QueueInterface
     */
    private $queue;
    
    /**
     * @var MessageInterface
     */
    private $message;

    /**
     * @param QueueInterface $queue
     * @param MessageInterface $message
     */
    public function __construct(QueueInterface $queue, MessageInterface $message)
    {
        $this->queue = $queue;
        $this->message = $message;
    }

    /**
     * @return QueueInterface
     */
    public function getQueue(): QueueInterface
    {
        return $this->queue;
    }

    /**
     * @return MessageInterface
     */
    public function getMessage(): MessageInterface
    {
        return $this->message;
    }
}
