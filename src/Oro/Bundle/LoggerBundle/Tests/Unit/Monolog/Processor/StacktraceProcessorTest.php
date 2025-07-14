<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Monolog\Processor;

use Monolog\Logger;
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
        $record = [
            'level' => Logger::WARNING,
            'message' => 'some message'
        ];
        $processor = $this->getStacktraceProcessor('error');
        self::assertEquals($record, $processor($record));
    }

    public function testRecordWithoutExceptionAndRecordLevelEqualsToStacktraceLogLevel(): void
    {
        $record = [
            'level' => Logger::ERROR,
            'message' => 'some message'
        ];
        $processor = $this->getStacktraceProcessor('error');
        self::assertEquals($record, $processor($record));
    }

    public function testRecordWithExceptionAndRecordLevelLessThanStacktraceLogLevel(): void
    {
        $record = [
            'level' => Logger::WARNING,
            'message' => 'some message',
            'context' => ['exception' => new \RuntimeException()]
        ];
        $processor = $this->getStacktraceProcessor('error');
        self::assertEquals($record, $processor($record));
    }

    public function testRecordWithExceptionAndRecordLevelEqualsToStacktraceLogLevel(): void
    {
        $record = [
            'level' => Logger::ERROR,
            'message' => 'some message',
            'context' => ['exception' => new \RuntimeException()]
        ];
        $processor = $this->getStacktraceProcessor('error');
        $processedRecord = $processor($record);
        self::assertArrayHasKey('stacktrace', $processedRecord['context']);
        $stacktrace = $processedRecord['context']['stacktrace'];
        self::assertStringContainsString(str_replace('::', '->', __METHOD__), $stacktrace);
        self::assertStringNotContainsString($this->getProjectDir(), $stacktrace);
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
        $processor = $this->getStacktraceProcessor('error');
        $processedRecord = $processor($record);
        self::assertArrayHasKey('stacktrace', $processedRecord['context']);
        $stacktrace = $processedRecord['context']['stacktrace'];
        self::assertStringContainsString(str_replace('::', '->', __METHOD__), $stacktrace);
        self::assertStringNotContainsString($this->getProjectDir(), $stacktrace);
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
        $processor = $this->getStacktraceProcessor(null);
        self::assertEquals($record, $processor($record));
    }

    public function testRecordWithExceptionAndRecordLevelLessThanStacktraceLogLevelAndStacktraceLogLevelIsEmpty(): void
    {
        $record = [
            'level' => Logger::ERROR,
            'message' => 'some message',
            'context' => ['exception' => new \RuntimeException()]
        ];
        $processor = $this->getStacktraceProcessor('');
        self::assertEquals($record, $processor($record));
    }

    public function testRecordWithExceptionAndRecordLevelLessThanStacktraceLogLevelAndStacktraceLogLevelIsNone(): void
    {
        $record = [
            'level' => Logger::ERROR,
            'message' => 'some message',
            'context' => ['exception' => new \RuntimeException()]
        ];
        $processor = $this->getStacktraceProcessor('none');
        self::assertEquals($record, $processor($record));
    }

    public function testStacktraceLogLevelIsInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->getStacktraceProcessor('other');
    }
}
