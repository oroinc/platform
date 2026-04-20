<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Monolog\Processor;

use Monolog\Level;
use Monolog\LogRecord;
use Oro\Bundle\LoggerBundle\Monolog\Processor\TraceProcessor;
use Oro\Bundle\LoggerBundle\Trace\TraceManager;
use Oro\Bundle\LoggerBundle\Trace\TraceManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TraceProcessorTest extends TestCase
{
    private const REQUEST_ID_KEY = 'traceId';

    private TraceManagerInterface $traceManager;
    private TraceProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->traceManager = new TraceManager($dispatcher);
        $this->processor = new TraceProcessor($this->traceManager);
    }

    private function createLogRecord(array $context = []): LogRecord
    {
        return new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'some message',
            context: $context
        );
    }

    public function testRecordWithoutTraceId(): void
    {
        $record = $this->createLogRecord();

        self::assertSame($record, ($this->processor)($record));
    }

    public function testRecordWithTraceIdAddsContext(): void
    {
        $trace = $this->traceManager->generate();
        $this->traceManager->set($trace);
        $record = $this->createLogRecord(['foo' => 'bar']);

        $processedRecord = ($this->processor)($record);

        self::assertSame($trace, $processedRecord->context[self::REQUEST_ID_KEY]);
        self::assertSame('bar', $processedRecord->context['foo']);
    }

    public function testRecordWithExistingTraceIdIsNotOverridden(): void
    {
        $newTrace = $this->traceManager->generate();
        $this->traceManager->set($newTrace);

        $record = $this->createLogRecord([self::REQUEST_ID_KEY => $this->traceManager->generate()]);

        self::assertSame($record, ($this->processor)($record));
    }
}
