<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption;

use Oro\Component\MessageQueue\Consumption\ChainExtension;
use Oro\Component\MessageQueue\Consumption\ConsumeMessagesCommand;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use Symfony\Component\Console\Tester\CommandTester;

class ConsumeMessagesCommandTest extends \PHPUnit\Framework\TestCase
{
    private QueueConsumer|\PHPUnit\Framework\MockObject\MockObject $consumer;

    private ConsumeMessagesCommand $command;

    protected function setUp(): void
    {
        $this->consumer = $this->createMock(QueueConsumer::class);

        $this->command = new ConsumeMessagesCommand($this->consumer);
    }

    public function testShouldHaveCommandName(): void
    {
        self::assertEquals('oro:message-queue:transport:consume', $this->command->getName());
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

        self::assertCount(2, $arguments);
        self::assertArrayHasKey('processor-service', $arguments);
        self::assertArrayHasKey('queue', $arguments);
    }

    public function testShouldExecuteConsumption(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('close');

        $this->consumer->expects(self::once())
            ->method('bind')
            ->with('queue-name', 'processor-service');
        $this->consumer->expects(self::once())
            ->method('consume')
            ->with(self::isInstanceOf(ChainExtension::class));
        $this->consumer->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $tester = new CommandTester($this->command);
        $tester->execute(['queue' => 'queue-name', 'processor-service' => 'processor-service']);
    }
}
