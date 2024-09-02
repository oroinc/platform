<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Monolog\Processor;

use Monolog\Logger;
use Oro\Bundle\LoggerBundle\Monolog\Processor\StacktraceProcessor;
use Psr\Log\InvalidArgumentException;

class StacktraceProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testRecordWithoutExceptionAndRecordLevelLessThanStacktraceLogLevel(): void
    {
        $record = [
            'level' => Logger::WARNING,
            'message' => 'some message'
        ];
        $processor = new StacktraceProcessor('error');
        self::assertEquals($record, $processor($record));
    }

    public function testRecordWithoutExceptionAndRecordLevelEqualsToStacktraceLogLevel(): void
    {
        $record = [
            'level' => Logger::ERROR,
            'message' => 'some message'
        ];
        $processor = new StacktraceProcessor('error');
        self::assertEquals($record, $processor($record));
    }

    public function testRecordWithExceptionAndRecordLevelLessThanStacktraceLogLevel(): void
    {
        $record = [
            'level' => Logger::WARNING,
            'message' => 'some message',
            'context' => ['exception' => new \RuntimeException()]
        ];
        $processor = new StacktraceProcessor('error');
        self::assertEquals($record, $processor($record));
    }

    public function testRecordWithExceptionAndRecordLevelEqualsToStacktraceLogLevel(): void
    {
        $record = [
            'level' => Logger::ERROR,
            'message' => 'some message',
            'context' => ['exception' => new \RuntimeException()]
        ];
        $processor = new StacktraceProcessor('error');
        $processedRecord = $processor($record);
        self::assertArrayHasKey('stacktrace', $processedRecord['context']);
        self::assertStringContainsString(
            str_replace('::', '->', __METHOD__),
            $processedRecord['context']['stacktrace']
        );
        unset($processedRecord['context']['stacktrace']);
        self::assertEquals($record, $processedRecord);
    }

    public function testRecordWithExceptionAndRecordLevelGreaterThanStacktraceLogLevel(): void
    {
        $record = [
            'level' => Logger::CRITICAL,
            'message' => 'some message',
            'context' => ['exception' => new \RuntimeException()]
        ];
        $processor = new StacktraceProcessor('error');
        $processedRecord = $processor($record);
        self::assertArrayHasKey('stacktrace', $processedRecord['context']);
        self::assertStringContainsString(
            str_replace('::', '->', __METHOD__),
            $processedRecord['context']['stacktrace']
        );
        unset($processedRecord['context']['stacktrace']);
        self::assertEquals($record, $processedRecord);
    }

    public function testRecordWithExceptionAndRecordLevelLessThanStacktraceLogLevelAndStacktraceLogLevelIsNull(): void
    {
        $record = [
            'level' => Logger::ERROR,
            'message' => 'some message',
            'context' => ['exception' => new \RuntimeException()]
        ];
        $processor = new StacktraceProcessor(null);
        self::assertEquals($record, $processor($record));
    }

    public function testRecordWithExceptionAndRecordLevelLessThanStacktraceLogLevelAndStacktraceLogLevelIsEmpty(): void
    {
        $record = [
            'level' => Logger::ERROR,
            'message' => 'some message',
            'context' => ['exception' => new \RuntimeException()]
        ];
        $processor = new StacktraceProcessor('');
        self::assertEquals($record, $processor($record));
    }

    public function testRecordWithExceptionAndRecordLevelLessThanStacktraceLogLevelAndStacktraceLogLevelIsNone(): void
    {
        $record = [
            'level' => Logger::ERROR,
            'message' => 'some message',
            'context' => ['exception' => new \RuntimeException()]
        ];
        $processor = new StacktraceProcessor('none');
        self::assertEquals($record, $processor($record));
    }

    public function testStacktraceLogLevelIsInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new StacktraceProcessor('other');
    }
}
