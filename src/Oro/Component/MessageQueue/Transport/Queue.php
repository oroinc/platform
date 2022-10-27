<?php

namespace Oro\Component\MessageQueue\Transport;

/**
 * Base Queue that implements Queue interface.
 * @see \Oro\Component\MessageQueue\Transport\QueueInterface
 */
class Queue implements QueueInterface
{
    /**
     * @var string
     */
    private $queueName;

    public function __construct(string $queueName)
    {
        $this->queueName = $queueName;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueueName(): string
    {
        return $this->queueName;
    }
}
