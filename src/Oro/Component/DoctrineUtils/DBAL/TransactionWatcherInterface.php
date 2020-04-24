<?php

namespace Oro\Component\DoctrineUtils\DBAL;

/**
 * Provides an interface for classes that need to watch the root DBAL transaction.
 */
interface TransactionWatcherInterface
{
    /**
     * This method is called after the root DBAL transaction is successfully started.
     */
    public function onTransactionStarted();

    /**
     * This method is called after the root DBAL transaction is successfully committed.
     */
    public function onTransactionCommitted();

    /**
     * This method is called after the root DBAL transaction is successfully rolled back.
     */
    public function onTransactionRolledback();
}
