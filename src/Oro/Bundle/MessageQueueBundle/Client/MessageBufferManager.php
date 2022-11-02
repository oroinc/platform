<?php

namespace Oro\Bundle\MessageQueueBundle\Client;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\DoctrineUtils\DBAL\TransactionWatcherAwareInterface;

/**
 * Provides a way to flush messages stored in the message buffer.
 */
class MessageBufferManager
{
    /** @var BufferedMessageProducer */
    private $bufferedProducer;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var string|null */
    private $connectionName;

    /**
     * @param BufferedMessageProducer $bufferedProducer
     * @param ManagerRegistry         $doctrine
     * @param string                  $connectionName
     */
    public function __construct(
        BufferedMessageProducer $bufferedProducer,
        ManagerRegistry $doctrine,
        string $connectionName = null
    ) {
        $this->bufferedProducer = $bufferedProducer;
        $this->doctrine = $doctrine;
        $this->connectionName = $connectionName;
    }

    /**
     * Flushes messages stored in the message buffer if the buffering enabled
     * and the buffer has at least one message.
     *
     * @param bool $force Whether the buffer should be flushed even if an open database transaction exists
     */
    public function flushBuffer(bool $force = false): void
    {
        if ($this->bufferedProducer->isBufferingEnabled()
            && $this->bufferedProducer->hasBufferedMessages()
            && ($force || $this->isFlushBufferRequired())
        ) {
            $this->bufferedProducer->flushBuffer();
        }
    }

    protected function isTransactionActive(Connection $connection): bool
    {
        return $connection->getTransactionNestingLevel() === 0;
    }

    private function isFlushBufferRequired(): bool
    {
        /** @var Connection $connection */
        $connection = $this->doctrine->getConnection($this->connectionName);
        if (!$connection instanceof TransactionWatcherAwareInterface) {
            return true;
        }

        return $this->isTransactionActive($connection);
    }
}
