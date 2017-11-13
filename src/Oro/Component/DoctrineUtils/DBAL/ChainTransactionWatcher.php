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
    public function onTransactionCommited()
    {
        foreach ($this->watchers as $watcher) {
            $watcher->onTransactionCommited();
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
