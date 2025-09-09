<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MessageQueueBundle\Client\BufferedMessageProducer;
use Oro\Bundle\MessageQueueBundle\Migration\MessageQueueMigrationQueryExecutor;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\MigrationQueryExecutorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MessageQueueMigrationQueryExecutorTest extends TestCase
{
    private MigrationQueryExecutorInterface&MockObject $innerExecutor;
    private BufferedMessageProducer&MockObject $producer;
    private MessageQueueMigrationQueryExecutor $executor;

    #[\Override]
    protected function setUp(): void
    {
        $this->innerExecutor = $this->createMock(MigrationQueryExecutorInterface::class);
        $this->producer = $this->createMock(BufferedMessageProducer::class);

        $this->executor = new MessageQueueMigrationQueryExecutor(
            $this->innerExecutor,
            $this->producer
        );
    }

    public function testGetConnection(): void
    {
        $connection = $this->createMock(Connection::class);

        $this->innerExecutor->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        self::assertSame($connection, $this->executor->getConnection());
    }

    public function testSetLogger(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $this->innerExecutor->expects(self::once())
            ->method('setLogger')
            ->with(self::identicalTo($logger));

        $this->executor->setLogger($logger);
    }

    public function testExecute(): void
    {
        $query = $this->createMock(MigrationQuery::class);

        $this->innerExecutor->expects(self::once())
            ->method('execute')
            ->with(self::identicalTo($query), self::isFalse());

        $this->producer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(true);
        $this->producer->expects(self::once())
            ->method('flushBuffer');

        $this->executor->execute($query, false);
    }

    public function testExecuteDryRun(): void
    {
        $query = $this->createMock(MigrationQuery::class);

        $this->innerExecutor->expects(self::once())
            ->method('execute')
            ->with(self::identicalTo($query), self::isTrue());

        $this->producer->expects(self::never())
            ->method('isBufferingEnabled');
        $this->producer->expects(self::never())
            ->method('flushBuffer');

        $this->executor->execute($query, true);
    }
}
