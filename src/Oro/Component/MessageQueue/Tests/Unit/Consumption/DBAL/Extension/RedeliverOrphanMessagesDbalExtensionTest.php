<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\DBAL\Extension;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Dbal\DbalCliProcessManager;
use Oro\Component\MessageQueue\Consumption\Dbal\DbalPidFileManager;
use Oro\Component\MessageQueue\Consumption\Dbal\Extension\RedeliverOrphanMessagesDbalExtension;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessageConsumer;
use Oro\Component\MessageQueue\Transport\Dbal\DbalSessionInterface;
use Psr\Log\LoggerInterface;

class RedeliverOrphanMessagesDbalExtensionTest extends \PHPUnit\Framework\TestCase
{
    private DbalPidFileManager|\PHPUnit\Framework\MockObject\MockObject $pidFileManager;

    private DbalCliProcessManager|\PHPUnit\Framework\MockObject\MockObject $cliProcessManager;

    private Connection|\PHPUnit\Framework\MockObject\MockObject $dbalConnection;

    private DbalSessionInterface|\PHPUnit\Framework\MockObject\MockObject $session;

    protected function setUp(): void
    {
        $this->pidFileManager = $this->createMock(DbalPidFileManager::class);
        $this->cliProcessManager = $this->createMock(DbalCliProcessManager::class);

        $this->dbalConnection = $this->createMock(Connection::class);

        $connection = $this->createMock(DbalConnection::class);
        $connection->expects(self::any())
            ->method('getDBALConnection')
            ->willReturn($this->dbalConnection);

        $this->session = $this->createMock(DbalSessionInterface::class);
        $this->session->expects(self::any())
            ->method('getConnection')
            ->willReturn($connection);
    }


    public function testCouldBeConstructedWithoutAnyArgument(): void
    {
        $this->expectNotToPerformAssertions();

        new RedeliverOrphanMessagesDbalExtension(
            $this->createMock(DbalPidFileManager::class),
            $this->createMock(DbalCliProcessManager::class),
            ':console'
        );
    }

    public function testShouldCreatePidFileOnlyOnce(): void
    {
        $consumer = $this->createMock(DbalMessageConsumer::class);
        $consumer->expects(self::once())
            ->method('getConsumerId')
            ->willReturn('consumer-id');

        $session = $this->createMock(DbalSessionInterface::class);

        $this->pidFileManager->expects(self::once())
            ->method('createPidFile')
            ->with('consumer-id');
        $this->pidFileManager->expects(self::once())
            ->method('getListOfPidsFileInfo')
            ->willReturn([]);

        $context = new Context($session);
        $context->setMessageConsumer($consumer);

        $extension = new RedeliverOrphanMessagesDbalExtension(
            $this->pidFileManager,
            $this->createMock(DbalCliProcessManager::class),
            ':console'
        );

        $extension->onBeforeReceive($context);
        $extension->onBeforeReceive($context);
    }

    public function testShouldRedeliverOrphanMessages(): void
    {
        $this->dbalConnection->expects(self::once())
            ->method('executeStatement')
            ->with(
                'UPDATE  SET consumer_id=NULL, redelivered=:isRedelivered ' .
                'WHERE consumer_id IN (:consumerIds)',
                [
                    'isRedelivered' => true,
                    'consumerIds' => ['consumer-id-1', 'consumer-id-2'],
                ],
                [
                    'isRedelivered' => Types::BOOLEAN,
                    'consumerIds' => Connection::PARAM_STR_ARRAY,
                ]
            );

        $this->pidFileManager->expects(self::once())
            ->method('getListOfPidsFileInfo')
            ->willReturn([
                ['pid' => 123, 'consumerId' => 'consumer-id-1'],
                ['pid' => 456, 'consumerId' => 'consumer-id-2'],
            ]);
        $this->pidFileManager->expects(self::exactly(2))
            ->method('removePidFile')
            ->withConsecutive(['consumer-id-1'], ['consumer-id-2']);

        $this->cliProcessManager->expects(self::once())
            ->method('getListOfProcessesPids')
            ->willReturn([]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('critical')
            ->with('Orphans were found and redelivered. consumerIds: "consumer-id-1, consumer-id-2"');

        $context = new Context($this->session);
        $context->setMessageConsumer($this->createMock(DbalMessageConsumer::class));
        $context->setLogger($logger);

        $extension = new RedeliverOrphanMessagesDbalExtension(
            $this->pidFileManager,
            $this->cliProcessManager,
            ':console'
        );

        $extension->onBeforeReceive($context);
        $extension->onBeforeReceive($context);
    }

    public function testShouldNotCheckForRunningProcessesIfNoPidsFileInfo(): void
    {
        $this->dbalConnection->expects(self::never())
            ->method('executeStatement');

        $this->pidFileManager->expects(self::once())
            ->method('getListOfPidsFileInfo')
            ->willReturn([]);

        $this->cliProcessManager->expects(self::never())
            ->method('getListOfProcessesPids')
            ->willReturn([]);

        $context = new Context($this->session);
        $context->setMessageConsumer($this->createMock(DbalMessageConsumer::class));

        $extension = new RedeliverOrphanMessagesDbalExtension(
            $this->pidFileManager,
            $this->cliProcessManager,
            ':console'
        );

        $extension->onBeforeReceive($context);
        $extension->onBeforeReceive($context);
    }

    public function testOnInterruptedShouldRemovePidFile(): void
    {
        $consumer = $this->createMock(DbalMessageConsumer::class);
        $consumer->expects(self::once())
            ->method('getConsumerId')
            ->willReturn('consumer-id');

        $session = $this->createMock(DbalSessionInterface::class);

        $context = new Context($session);
        $context->setMessageConsumer($consumer);

        $this->pidFileManager->expects(self::once())
            ->method('removePidFile')
            ->with('consumer-id');

        $extension = new RedeliverOrphanMessagesDbalExtension(
            $this->pidFileManager,
            $this->createMock(DbalCliProcessManager::class),
            ':console'
        );

        $extension->onInterrupted($context);
    }
}
