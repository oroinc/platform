<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Log\Handler;

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
        self::assertFalse($this->handler->isHandling([]));
    }

    public function testIsHandlingWithJob(): void
    {
        $this->consumerState->setJob($this->createMock(Job::class));
        self::assertTrue($this->handler->isHandling([]));
    }

    public function testHandleWithoutJob(): void
    {
        $record = [
            'message' => 'test message',
            'level'   => 100,
            'channel' => 'test_channel',
            'context' => []
        ];

        $this->jobLogger->expects(self::never())
            ->method('log');

        self::assertFalse($this->handler->handle($record));
    }

    public function testHandleWithJob(): void
    {
        $record = [
            'message' => 'test message',
            'level'   => 100,
            'channel' => 'test_channel',
            'context' => ['key' => 'value']
        ];

        $this->jobLogger->expects(self::once())
            ->method('log')
            ->with(
                $record['level'],
                $record['message'],
                ['key' => 'value', 'log_channel' => $record['channel']]
            );

        $this->consumerState->setJob($this->createMock(Job::class));
        self::assertFalse($this->handler->handle($record));
    }
}
