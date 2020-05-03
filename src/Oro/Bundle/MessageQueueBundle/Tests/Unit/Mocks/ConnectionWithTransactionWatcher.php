<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Mocks;

use Doctrine\DBAL\Connection;
use Oro\Component\DoctrineUtils\DBAL\TransactionWatcherAwareInterface;
use Oro\Component\DoctrineUtils\DBAL\TransactionWatcherInterface;

class ConnectionWithTransactionWatcher extends Connection implements TransactionWatcherAwareInterface
{
    /** @var TransactionWatcherInterface|null */
    private $transactionWatcher;

    /**
     * {@inheritdoc}
     */
    public function setTransactionWatcher(TransactionWatcherInterface $transactionWatcher = null)
    {
        $this->transactionWatcher = $transactionWatcher;
    }
}
