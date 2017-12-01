<?php

namespace Oro\Component\DoctrineUtils\DBAL;

/**
 * This interface should be implemented by classes that depends on a TransactionWatcher.
 */
interface TransactionWatcherAwareInterface
{
    /**
     * Sets the transaction watcher.
     *
     * @param TransactionWatcherInterface|null $transactionWatcher
     */
    public function setTransactionWatcher(TransactionWatcherInterface $transactionWatcher = null);
}
