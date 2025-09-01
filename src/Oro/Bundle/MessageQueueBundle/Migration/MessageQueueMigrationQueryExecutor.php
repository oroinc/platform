<?php

namespace Oro\Bundle\MessageQueueBundle\Migration;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MessageQueueBundle\Client\BufferedMessageProducer;
use Oro\Bundle\MigrationBundle\Migration\MigrationQueryExecutorInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Psr\Log\LoggerInterface;

/**
 * Flushes message queue buffer when there are messages in the buffer and the dry run was not requested.
 */
class MessageQueueMigrationQueryExecutor implements MigrationQueryExecutorInterface
{
    private MigrationQueryExecutorInterface $migrationQueryExecutor;
    private MessageProducerInterface $producer;

    public function __construct(
        MigrationQueryExecutorInterface $migrationQueryExecutor,
        MessageProducerInterface $producer
    ) {
        $this->migrationQueryExecutor = $migrationQueryExecutor;
        $this->producer = $producer;
    }

    #[\Override]
    public function getConnection(): Connection
    {
        return $this->migrationQueryExecutor->getConnection();
    }

    #[\Override]
    public function setLogger(LoggerInterface $logger): void
    {
        $this->migrationQueryExecutor->setLogger($logger);
    }

    #[\Override]
    public function execute($query, $dryRun): void
    {
        $this->migrationQueryExecutor->execute($query, $dryRun);
        if (!$dryRun && $this->producer instanceof BufferedMessageProducer && $this->producer->isBufferingEnabled()) {
            $this->producer->flushBuffer();
        }
    }
}
