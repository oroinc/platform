<?php

namespace Oro\Bundle\MessageQueueBundle\Client;

use Oro\Component\DoctrineUtils\DBAL\TransactionWatcherInterface;

/**
 * Watches DBAL transaction in order to enable the buffering mode when the root transaction starts
 * and send all collected messages when the root transaction is commited
 * or remove all collected messages from the buffer without sending them when the root transaction is rolled back.
 */
class DbalTransactionWatcher implements TransactionWatcherInterface
{
    /** @var BufferedMessageProducer */
    private $producer;

    /**
     * @param BufferedMessageProducer $producer
     */
    public function __construct(BufferedMessageProducer $producer)
    {
        $this->producer = $producer;
    }

    /**
     * {@inheritdoc}
     */
    public function onTransactionStarted()
    {
        $this->producer->enableBuffering();
    }

    /**
     * {@inheritdoc}
     */
    public function onTransactionCommited()
    {
        try {
            $this->producer->flushBuffer();
        } finally {
            // the buffering should be disabled independing on the flush result
            $this->producer->disableBuffering();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onTransactionRolledback()
    {
        $this->producer->clearBuffer();
        $this->producer->disableBuffering();
    }
}
