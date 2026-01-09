<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Monolog\Handler;

use Monolog\Level;
use Monolog\LogRecord;
use Oro\Bundle\ApiBundle\Collector\ApiDocWarningsCollector;
use Oro\Bundle\ApiBundle\Monolog\Handler\ApiDocCollectingHandler;
use PHPUnit\Framework\TestCase;

class ApiDocCollectingHandlerTest extends TestCase
{
    private ApiDocWarningsCollector $collector;
    private ApiDocCollectingHandler $handler;

    protected function setUp(): void
    {
        $this->collector = $this->createMock(ApiDocWarningsCollector::class);
        $this->handler = new ApiDocCollectingHandler($this->collector);
    }

    public function testHandleAddsWarningToCollector(): void
    {
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Warning,
            message: 'Test warning message'
        );

        $this->collector->expects(self::once())
            ->method('addWarning')
            ->with('Test warning message');

        $this->handler->handle($record);
    }

    public function testHandleDeduplicatesMessages(): void
    {
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Warning,
            message: 'Duplicate warning'
        );

        $this->collector->expects(self::once())
            ->method('addWarning')
            ->with('Duplicate warning');

        // First call should add warning
        $this->handler->handle($record);

        // Second call with same message should not add warning
        $this->handler->handle($record);
    }

    public function testHandleProcessesDifferentMessages(): void
    {
        $record1 = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Warning,
            message: 'Warning message 1'
        );
        $record2 = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Warning,
            message: 'Warning message 2'
        );

        $this->collector->expects(self::exactly(2))
            ->method('addWarning')
            ->withConsecutive(['Warning message 1'], ['Warning message 2']);

        $this->handler->handle($record1);
        $this->handler->handle($record2);
    }

    public function testHandleAlwaysReturnsFalse(): void
    {
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Warning,
            message: 'Any message'
        );

        $result = $this->handler->handle($record);

        self::assertFalse($result);
    }

    public function testHandleWithEmptyMessage(): void
    {
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Warning,
            message: ''
        );

        $this->collector->expects(self::once())
            ->method('addWarning')
            ->with('');

        $result = $this->handler->handle($record);

        self::assertFalse($result);
    }

    public function testHandleDeduplicatesEmptyMessages(): void
    {
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Warning,
            message: ''
        );

        $this->collector->expects(self::once())
            ->method('addWarning')
            ->with('');

        $this->handler->handle($record);
        $this->handler->handle($record);
    }
}
