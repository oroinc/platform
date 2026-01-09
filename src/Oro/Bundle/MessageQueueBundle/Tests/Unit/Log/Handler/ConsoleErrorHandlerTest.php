<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Log\Handler;

use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
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
        $emptyRecord = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Debug,
            message: 'test'
        );
        $this->assertFalse($this->handler->isHandling($emptyRecord));

        $this->consumerState->startConsumption();

        $criticalRecord = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Critical,
            message: 'test'
        );
        $this->assertTrue($this->handler->isHandling($criticalRecord));

        $debugRecord = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Debug,
            message: 'test'
        );
        $this->assertFalse($this->handler->isHandling($debugRecord));
    }

    public function testReset(): void
    {
        $this->innerHandler->expects(self::once())
            ->method('reset');

        $this->handler->reset();
    }
}
