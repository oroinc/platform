<?php

namespace Oro\Component\DoctrineUtils\DBAL;

/**
 * This interface should be implemented by classes that depends on a TransactionWatcher.
 */
interface TransactionWatcherAwareInterface
{
    /**
     * Sets the transaction watcher.
     */
    public function setTransactionWatcher(TransactionWatcherInterface $transactionWatcher = null);
}
