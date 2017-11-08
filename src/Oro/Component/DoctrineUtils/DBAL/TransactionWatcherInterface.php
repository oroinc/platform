<?php

namespace Oro\Component\DoctrineUtils\DBAL;

/**
 * Provides an interface for classes that need to watch the root DBAL transaction.
 */
interface TransactionWatcherInterface
{
    /**
     * This method is called after the root DBAL transaction is sucessfully started.
     */
    public function onTransactionStarted();

    /**
     * This method is called after the root DBAL transaction is sucessfully commited.
     */
    public function onTransactionCommited();

    /**
     * This method is called after the root DBAL transaction is sucessfully rolled back.
     */
    public function onTransactionRolledback();
}
