<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Log\Handler;

use Monolog\Level;
use Monolog\LogRecord;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Log\ConsumerState;
use Oro\Component\MessageQueue\Log\Handler\ResendJobHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ResendJobHandlerTest extends TestCase
{
    private LoggerInterface&MockObject $jobLogger;
    private ConsumerState $consumerState;
    private ResendJobHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->jobLogger = $this->createMock(LoggerInterface::class);
        $this->consumerState = new ConsumerState();

        $this->handler = new ResendJobHandler($this->jobLogger, $this->consumerState);
    }

    public function testIsHandlingWithoutJob(): void
    {
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Debug,
            message: 'test'
        );
        self::assertFalse($this->handler->isHandling($record));
    }

    public function testIsHandlingWithJob(): void
    {
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Debug,
            message: 'test'
        );
        $this->consumerState->setJob($this->createMock(Job::class));
        self::assertTrue($this->handler->isHandling($record));
    }

    public function testHandleWithoutJob(): void
    {
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test_channel',
            level: Level::Debug,
            message: 'test message',
            context: []
        );

        $this->jobLogger->expects(self::never())
            ->method('log');

        self::assertFalse($this->handler->handle($record));
    }

    public function testHandleWithJob(): void
    {
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test_channel',
            level: Level::Debug,
            message: 'test message',
            context: ['key' => 'value']
        );

        $this->jobLogger->expects(self::once())
            ->method('log')
            ->with(
                Level::Debug->value,
                'test message',
                ['key' => 'value', 'log_channel' => 'test_channel']
            );

        $this->consumerState->setJob($this->createMock(Job::class));
        self::assertFalse($this->handler->handle($record));
    }
}
