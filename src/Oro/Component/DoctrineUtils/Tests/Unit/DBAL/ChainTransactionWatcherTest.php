<?php

namespace Oro\Component\DoctrineUtils\Tests\Unit\DBAL;

use Oro\Component\DoctrineUtils\DBAL\ChainTransactionWatcher;
use Oro\Component\DoctrineUtils\DBAL\TransactionWatcherInterface;

class ChainTransactionWatcherTest extends \PHPUnit\Framework\TestCase
{
    /** @var TransactionWatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $watcher1;

    /** @var TransactionWatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $watcher2;

    /** @var ChainTransactionWatcher */
    private $chainWatcher;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->watcher1 = $this->createMock(TransactionWatcherInterface::class);
        $this->watcher2 = $this->createMock(TransactionWatcherInterface::class);

        $this->chainWatcher = new ChainTransactionWatcher([$this->watcher1, $this->watcher2]);
    }

    public function testOnTransactionStarted()
    {
        $this->watcher1->expects(self::once())
            ->method('onTransactionStarted');
        $this->watcher2->expects(self::once())
            ->method('onTransactionStarted');

        $this->chainWatcher->onTransactionStarted();
    }

    public function testOnTransactionCommited()
    {
        $this->watcher1->expects(self::once())
            ->method('onTransactionCommited');
        $this->watcher2->expects(self::once())
            ->method('onTransactionCommited');

        $this->chainWatcher->onTransactionCommited();
    }

    public function testOnTransactionRolledback()
    {
        $this->watcher1->expects(self::once())
            ->method('onTransactionRolledback');
        $this->watcher2->expects(self::once())
            ->method('onTransactionRolledback');

        $this->chainWatcher->onTransactionRolledback();
    }
}
