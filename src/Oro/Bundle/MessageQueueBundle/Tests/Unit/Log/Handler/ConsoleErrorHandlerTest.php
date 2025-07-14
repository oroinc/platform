<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Log\Handler;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Oro\Bundle\MessageQueueBundle\Log\Handler\ConsoleErrorHandler;
use Oro\Component\MessageQueue\Log\ConsumerState;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConsoleErrorHandlerTest extends TestCase
{
    private ConsumerState $consumerState;
    private TestHandler&MockObject $innerHandler;
    private ConsoleErrorHandler $handler;

    #[\Override]
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
