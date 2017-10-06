<?php

namespace Oro\Component\MessageQueue\Transport;

/**
 * Provides an interface that gives the opportunity to work with transactions for message producer.
 * @TODO Will be deleted in the scope of BAP-14987 story
 */
interface TransactionInterface
{
    /**
     * Starts a transaction. All sent messages should be committed
     *
     * @return void
     */
    public function startTransaction();

    /**
     * Commits the current transaction.
     *
     * @return void
     */
    public function commit();

    /**
     * Drops any sent messages to queue during the current transaction.
     *
     * @return void
     */
    public function rollBack();
}
