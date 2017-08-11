<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Log\Handler;

use Psr\Log\LoggerInterface;

use Oro\Component\MessageQueue\Job\Job;
use Oro\Bundle\MessageQueueBundle\Log\ConsumerState;
use Oro\Bundle\MessageQueueBundle\Log\Handler\ResendJobHandler;

class ResendJobHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|LoggerInterface */
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
