<?php

namespace Oro\Component\DoctrineUtils\Tests\Unit\DBAL;

use Oro\Component\DoctrineUtils\DBAL\ChainTransactionWatcher;
use Oro\Component\DoctrineUtils\DBAL\TransactionWatcherInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChainTransactionWatcherTest extends TestCase
{
    private TransactionWatcherInterface&MockObject $watcher1;
    private TransactionWatcherInterface&MockObject $watcher2;
    private ChainTransactionWatcher $chainWatcher;

    #[\Override]
    protected function setUp(): void
    {
        $this->watcher1 = $this->createMock(TransactionWatcherInterface::class);
        $this->watcher2 = $this->createMock(TransactionWatcherInterface::class);

        $this->chainWatcher = new ChainTransactionWatcher([$this->watcher1, $this->watcher2]);
    }

    public function testOnTransactionStarted(): void
    {
        $this->watcher1->expects(self::once())
            ->method('onTransactionStarted');
        $this->watcher2->expects(self::once())
            ->method('onTransactionStarted');

        $this->chainWatcher->onTransactionStarted();
    }

    public function testOnTransactionCommitted(): void
    {
        $this->watcher1->expects(self::once())
            ->method('onTransactionCommitted');
        $this->watcher2->expects(self::once())
            ->method('onTransactionCommitted');

        $this->chainWatcher->onTransactionCommitted();
    }

    public function testOnTransactionRolledback(): void
    {
        $this->watcher1->expects(self::once())
            ->method('onTransactionRolledback');
        $this->watcher2->expects(self::once())
            ->method('onTransactionRolledback');

        $this->chainWatcher->onTransactionRolledback();
    }
}
