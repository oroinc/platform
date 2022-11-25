<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Log\Handler;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Oro\Bundle\MessageQueueBundle\Log\Handler\ConsoleErrorHandler;
use Oro\Component\MessageQueue\Log\ConsumerState;

class ConsoleErrorHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConsumerState */
    private $consumerState;

    /** @var TestHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $innerHandler;

    /** @var ConsoleErrorHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->consumerState = new ConsumerState();
        $this->innerHandler = $this->createMock(TestHandler::class);

        $this->handler = new ConsoleErrorHandler($this->consumerState, $this->innerHandler, Logger::CRITICAL);
    }

    public function testIsHandling(): void
    {
        $this->assertFalse($this->handler->isHandling([]));
        $this->consumerState->startConsumption();
        $this->assertTrue($this->handler->isHandling(['level' => Logger::CRITICAL]));
        $this->assertFalse($this->handler->isHandling(['level' => Logger::DEBUG]));
    }

    public function testReset(): void
    {
        $this->innerHandler->expects(self::once())
            ->method('reset');

        $this->handler->reset();
    }
}
