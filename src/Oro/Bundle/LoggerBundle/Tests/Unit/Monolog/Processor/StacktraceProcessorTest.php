<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Monolog\Processor;

use Monolog\Level;
use Monolog\LogRecord;
use Oro\Bundle\LoggerBundle\Monolog\Processor\StacktraceProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Log\InvalidArgumentException;
use Symfony\Component\HttpKernel\Kernel;

class StacktraceProcessorTest extends TestCase
{
    private function getStacktraceProcessor(?string $level): StacktraceProcessor
    {
        return new StacktraceProcessor($level, $this->getProjectDir());
    }

    private function getProjectDir(): string
    {
        $kernelFileName = (new \ReflectionClass(Kernel::class))->getFileName();

        return substr(
            $kernelFileName,
            0,
            strpos($kernelFileName, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)
        );
    }

    public function testRecordWithoutExceptionAndRecordLevelLessThanStacktraceLogLevel(): void
    {
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Warning,
            message: 'some message'
        );
        $processor = $this->getStacktraceProcessor('error');
        self::assertEquals($record, $processor($record));
    }

    public function testRecordWithoutExceptionAndRecordLevelEqualsToStacktraceLogLevel(): void
    {
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Error,
            message: 'some message'
        );
        $processor = $this->getStacktraceProcessor('error');
        self::assertEquals($record, $processor($record));
    }

    public function testRecordWithExceptionAndRecordLevelLessThanStacktraceLogLevel(): void
    {
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Warning,
            message: 'some message',
            context: ['exception' => new \RuntimeException()]
        );
        $processor = $this->getStacktraceProcessor('error');
        self::assertEquals($record, $processor($record));
    }

    public function testRecordWithExceptionAndRecordLevelEqualsToStacktraceLogLevel(): void
    {
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Error,
            message: 'some message',
            context: ['exception' => new \RuntimeException()]
        );
        $processor = $this->getStacktraceProcessor('error');
        $processedRecord = $processor($record);
        self::assertArrayHasKey('stacktrace', $processedRecord->context);
        $stacktrace = $processedRecord->context['stacktrace'];
        self::assertStringContainsString(str_replace('::', '->', __METHOD__), $stacktrace);
        self::assertStringNotContainsString($this->getProjectDir(), $stacktrace);

        // Compare the record without stacktrace
        $contextWithoutStacktrace = $processedRecord->context;
        unset($contextWithoutStacktrace['stacktrace']);
        self::assertEquals($record->context, $contextWithoutStacktrace);
    }

    public function testRecordWithExceptionAndRecordLevelGreaterThanStacktraceLogLevel(): void
    {
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Critical,
            message: 'some message',
            context: ['exception' => new \RuntimeException()]
        );
        $processor = $this->getStacktraceProcessor('error');
        $processedRecord = $processor($record);
        self::assertArrayHasKey('stacktrace', $processedRecord->context);
        $stacktrace = $processedRecord->context['stacktrace'];
        self::assertStringContainsString(str_replace('::', '->', __METHOD__), $stacktrace);
        self::assertStringNotContainsString($this->getProjectDir(), $stacktrace);

        // Compare the record without stacktrace
        $contextWithoutStacktrace = $processedRecord->context;
        unset($contextWithoutStacktrace['stacktrace']);
        self::assertEquals($record->context, $contextWithoutStacktrace);
    }

    public function testRecordWithExceptionAndRecordLevelLessThanStacktraceLogLevelAndStacktraceLogLevelIsNull(): void
    {
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Error,
            message: 'some message',
            context: ['exception' => new \RuntimeException()]
        );
        $processor = $this->getStacktraceProcessor(null);
        self::assertEquals($record, $processor($record));
    }

    public function testRecordWithExceptionAndRecordLevelLessThanStacktraceLogLevelAndStacktraceLogLevelIsEmpty(): void
    {
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Error,
            message: 'some message',
            context: ['exception' => new \RuntimeException()]
        );
        $processor = $this->getStacktraceProcessor('');
        self::assertEquals($record, $processor($record));
    }

    public function testRecordWithExceptionAndRecordLevelLessThanStacktraceLogLevelAndStacktraceLogLevelIsNone(): void
    {
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Error,
            message: 'some message',
            context: ['exception' => new \RuntimeException()]
        );
        $processor = $this->getStacktraceProcessor('none');
        self::assertEquals($record, $processor($record));
    }

    public function testStacktraceLogLevelIsInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->getStacktraceProcessor('other');
    }
}
