<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Client;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\MessageQueueBundle\Client\BufferedMessageProducer;
use Oro\Bundle\MessageQueueBundle\Client\MessageBufferManager;
use Oro\Bundle\MessageQueueBundle\Tests\Unit\Mocks\ConnectionWithTransactionWatcher;

class MessageBufferManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var BufferedMessageProducer|\PHPUnit\Framework\MockObject\MockObject */
    private $bufferedProducer;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    protected function setUp(): void
    {
        $this->bufferedProducer = $this->createMock(BufferedMessageProducer::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
    }

    private function getMessageBufferManager(string $connectionName = null): MessageBufferManager
    {
        return new MessageBufferManager($this->bufferedProducer, $this->doctrine, $connectionName);
    }

    public function testFlushBufferWhenBufferingDisabled()
    {
        $this->bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(false);
        $this->bufferedProducer->expects(self::never())
            ->method('hasBufferedMessages');
        $this->bufferedProducer->expects(self::never())
            ->method('flushBuffer');

        $manager = $this->getMessageBufferManager();
        $manager->flushBuffer();
    }

    public function testFlushBufferWhenNoMessagedInBuffer()
    {
        $this->bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(true);
        $this->bufferedProducer->expects(self::once())
            ->method('hasBufferedMessages')
            ->willReturn(false);
        $this->bufferedProducer->expects(self::never())
            ->method('flushBuffer');

        $manager = $this->getMessageBufferManager();
        $manager->flushBuffer();
    }

    public function testFlushBufferWhenConnectionIsNotTransactionWatcherAware()
    {
        $this->bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(true);
        $this->bufferedProducer->expects(self::once())
            ->method('hasBufferedMessages')
            ->willReturn(true);
        $connection = $this->createMock(Connection::class);
        $this->doctrine->expects(self::once())
            ->method('getConnection')
            ->with(self::isNull())
            ->willReturn($connection);
        $this->bufferedProducer->expects(self::once())
            ->method('flushBuffer');

        $manager = $this->getMessageBufferManager();
        $manager->flushBuffer();
    }

    public function testFlushBufferWithoutActiveTransaction()
    {
        $this->bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(true);
        $this->bufferedProducer->expects(self::once())
            ->method('hasBufferedMessages')
            ->willReturn(true);
        $connection = $this->createMock(ConnectionWithTransactionWatcher::class);
        $connection->expects(self::once())
            ->method('getTransactionNestingLevel')
            ->willReturn(0);
        $this->doctrine->expects(self::once())
            ->method('getConnection')
            ->with(self::isNull())
            ->willReturn($connection);
        $this->bufferedProducer->expects(self::once())
            ->method('flushBuffer');

        $manager = $this->getMessageBufferManager();
        $manager->flushBuffer();
    }

    public function testFlushBufferWithActiveTransaction()
    {
        $this->bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(true);
        $this->bufferedProducer->expects(self::once())
            ->method('hasBufferedMessages')
            ->willReturn(true);
        $connection = $this->createMock(ConnectionWithTransactionWatcher::class);
        $connection->expects(self::once())
            ->method('getTransactionNestingLevel')
            ->willReturn(1);
        $this->doctrine->expects(self::once())
            ->method('getConnection')
            ->with(self::isNull())
            ->willReturn($connection);
        $this->bufferedProducer->expects(self::never())
            ->method('flushBuffer');

        $manager = $this->getMessageBufferManager();
        $manager->flushBuffer();
    }

    public function testForceFlushBuffer()
    {
        $this->bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(true);
        $this->bufferedProducer->expects(self::once())
            ->method('hasBufferedMessages')
            ->willReturn(true);
        $this->doctrine->expects(self::never())
            ->method('getConnection');
        $this->bufferedProducer->expects(self::once())
            ->method('flushBuffer');

        $manager = $this->getMessageBufferManager();
        $manager->flushBuffer(true);
    }

    public function testFlushBufferWithNotDefaultConnection()
    {
        $connectionName = 'test_connection';
        $this->bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(true);
        $this->bufferedProducer->expects(self::once())
            ->method('hasBufferedMessages')
            ->willReturn(true);
        $connection = $this->createMock(ConnectionWithTransactionWatcher::class);
        $connection->expects(self::once())
            ->method('getTransactionNestingLevel')
            ->willReturn(0);
        $this->doctrine->expects(self::once())
            ->method('getConnection')
            ->with($connectionName)
            ->willReturn($connection);
        $this->bufferedProducer->expects(self::once())
            ->method('flushBuffer');

        $manager = $this->getMessageBufferManager($connectionName);
        $manager->flushBuffer();
    }
}
