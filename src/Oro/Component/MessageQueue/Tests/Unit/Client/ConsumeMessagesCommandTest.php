<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Oro\Component\MessageQueue\Client\ConsumeMessagesCommand;
use Oro\Component\MessageQueue\Client\Meta\DestinationMeta;
use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;
use Oro\Component\MessageQueue\Consumption\ChainExtension;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use Symfony\Component\Console\Tester\CommandTester;

class ConsumeMessagesCommandTest extends \PHPUnit\Framework\TestCase
{
    private ConsumeMessagesCommand $command;

    private QueueConsumer|\PHPUnit\Framework\MockObject\MockObject $consumer;

    private DestinationMetaRegistry|\PHPUnit\Framework\MockObject\MockObject $registry;

    protected function setUp(): void
    {
        $this->consumer = $this->createMock(QueueConsumer::class);
        $this->registry = $this->createMock(DestinationMetaRegistry::class);

        $this->command = new ConsumeMessagesCommand($this->consumer, $this->registry);
    }

    public function testShouldHaveCommandName(): void
    {
        self::assertEquals('oro:message-queue:consume', $this->command->getName());
    }

    public function testShouldHaveExpectedOptions(): void
    {
        $options = $this->command->getDefinition()->getOptions();

        self::assertCount(6, $options);
        self::assertArrayHasKey('memory-limit', $options);
        self::assertArrayHasKey('message-limit', $options);
        self::assertArrayHasKey('time-limit', $options);
        self::assertArrayHasKey('object-limit', $options);
        self::assertArrayHasKey('gc-limit', $options);
        self::assertArrayHasKey('stop-when-unique-jobs-processed', $options);
    }

    public function testShouldHaveExpectedAttributes(): void
    {
        $arguments = $this->command->getDefinition()->getArguments();

        self::assertCount(1, $arguments);
        self::assertArrayHasKey('clientDestinationName', $arguments);
    }

    public function testShouldExecuteConsumptionAndUseDefaultQueueName(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('close');

        $this->consumer->expects(self::once())
            ->method('bind')
            ->with('aprefixt.adefaultqueuename');
        $this->consumer->expects(self::once())
            ->method('consume')
            ->with(self::isInstanceOf(ChainExtension::class));
        $this->consumer->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->registry->expects(self::once())
            ->method('getDestinationsMeta')
            ->willReturn([new DestinationMeta('aclient', 'aprefixt.adefaultqueuename')]);

        $tester = new CommandTester($this->command);
        $tester->execute([]);
    }

    public function testShouldExecuteConsumptionAndUseCustomClientDestinationName(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('close');

        $this->consumer->expects(self::once())
            ->method('bind')
            ->with('aprefixt.non-default-queue');
        $this->consumer->expects(self::once())
            ->method('consume')
            ->with(self::isInstanceOf(ChainExtension::class));
        $this->consumer->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->registry->expects(self::once())
            ->method('getDestinationMeta')
            ->with('non-default-queue')
            ->willReturn(new DestinationMeta('aclient', 'aprefixt.non-default-queue'));

        $tester = new CommandTester($this->command);
        $tester->execute(['clientDestinationName' => 'non-default-queue']);
    }

    public function testShouldExecuteConsumptionAndUseCustomClientDestinationNameWithCustomQueueFromArgument(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('close');

        $this->consumer->expects(self::once())
            ->method('bind')
            ->with('non-default-transport-queue');
        $this->consumer->expects(self::once())
            ->method('consume')
            ->with(self::isInstanceOf(ChainExtension::class));
        $this->consumer->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->registry->expects(self::once())
            ->method('getDestinationMeta')
            ->with('non-default-queue')
            ->willReturn(new DestinationMeta('aclient', 'non-default-transport-queue'));

        $tester = new CommandTester($this->command);
        $tester->execute(['clientDestinationName' => 'non-default-queue']);
    }

    public function testShouldLogErrorAndThrowExceptionIfConsumeThrowsException(): void
    {
        $expectedException = new \Exception('the message');

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('close');

        $this->consumer->expects(self::once())
            ->method('bind')
            ->with('aprefixt.adefaultqueuename');
        $this->consumer->expects(self::once())
            ->method('consume')
            ->with(self::isInstanceOf(ChainExtension::class))
            ->willThrowException($expectedException);
        $this->consumer->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->registry->expects(self::once())
            ->method('getDestinationsMeta')
            ->willReturn([new DestinationMeta('aclient', 'aprefixt.adefaultqueuename')]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage($expectedException->getMessage());

        $tester = new CommandTester($this->command);
        $tester->execute([]);
    }
}
