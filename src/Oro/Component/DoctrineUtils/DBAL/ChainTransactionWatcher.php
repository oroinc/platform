<?php

namespace Oro\Component\DoctrineUtils\DBAL;

/**
 * Provides a way to iterate by several DBAL transaction watcher.
 */
class ChainTransactionWatcher implements TransactionWatcherInterface
{
    /** @var TransactionWatcherInterface[] */
    private $watchers;

    /**
     * @param TransactionWatcherInterface[] $watchers
     */
    public function __construct(array $watchers)
    {
        $this->watchers = $watchers;
    }

    /**
     * {@inheritdoc}
     */
    public function onTransactionStarted()
    {
        foreach ($this->watchers as $watcher) {
            $watcher->onTransactionStarted();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onTransactionCommitted()
    {
        foreach ($this->watchers as $watcher) {
            $watcher->onTransactionCommitted();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onTransactionRolledback()
    {
        foreach ($this->watchers as $watcher) {
            $watcher->onTransactionRolledback();
        }
    }
}
