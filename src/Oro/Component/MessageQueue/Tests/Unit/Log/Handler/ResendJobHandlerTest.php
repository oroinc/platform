<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Log\Handler;

use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Log\ConsumerState;
use Oro\Component\MessageQueue\Log\Handler\ResendJobHandler;
use Psr\Log\LoggerInterface;

class ResendJobHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface */
    private $jobLogger;

    /** @var ConsumerState */
    private $consumerState;

    /** @var ResendJobHandler */
    private $handler;

    protected function setUp()
    {
        $this->jobLogger = $this->createMock(LoggerInterface::class);
        $this->consumerState = new ConsumerState();

        $this->handler = new ResendJobHandler($this->jobLogger, $this->consumerState);
    }

    public function testIsHandlingWithoutJob()
    {
        self::assertFalse($this->handler->isHandling([]));
    }

    public function testIsHandlingWithJob()
    {
        $this->consumerState->setJob($this->createMock(Job::class));
        self::assertTrue($this->handler->isHandling([]));
    }

    public function testHandleWithoutJob()
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

    public function testHandleWithJob()
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
