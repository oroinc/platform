<?php
declare(strict_types=1);

namespace Oro\Component\Log\Tests\Unit;

use Psr\Log\LoggerInterface;

class LogAndThrowExceptionTraitTest extends \PHPUnit\Framework\TestCase
{
    public function testThrowErrorException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Some message with data1 and data2.');
        $this->expectExceptionCode(12345);

        $previousException = new \UnexpectedValueException('Unexpected value.');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(static::once())
            ->method('error')
            ->with(
                'Some message with {value1} and {value2}.',
                [
                    'value1' => 'data1',
                    'value2' => 'data2',
                    'called_in' => __DIR__ . \DIRECTORY_SEPARATOR . 'LogAndThrowExceptionTraitUsingClassStub.php'
                        . ':' . LogAndThrowExceptionTraitUsingClassStub::THROW_ERROR_LINE_NUMBER,
                    'exception' => $previousException
                ]
            );
        $stub = new LogAndThrowExceptionTraitUsingClassStub($logger);
        $stub->xthrowErrorException(
            \RuntimeException::class,
            'Some message with {value1} and {value2}.',
            ['value1' => 'data1', 'value2' => 'data2'],
            $previousException,
            12345
        );
    }

    public function testThrowErrorExceptionWithoutLogger(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Some message with data1 and data2.');
        $this->expectExceptionCode(12345);

        $previousException = new \UnexpectedValueException('Unexpected value.');

        $stub = new LogAndThrowExceptionTraitUsingClassStub(null);
        $stub->xthrowErrorException(
            \RuntimeException::class,
            'Some message with {value1} and {value2}.',
            ['value1' => 'data1', 'value2' => 'data2'],
            $previousException,
            12345
        );
    }

    public function testThrowErrorExceptionWithoutPreviousException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Some message with data1 and data2.');
        $this->expectExceptionCode(12345);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(static::once())
            ->method('error')
            ->with(
                'Some message with {value1} and {value2}.',
                [
                    'value1' => 'data1',
                    'value2' => 'data2',
                    'called_in' => __DIR__ . \DIRECTORY_SEPARATOR . 'LogAndThrowExceptionTraitUsingClassStub.php'
                        . ':' . LogAndThrowExceptionTraitUsingClassStub::THROW_ERROR_LINE_NUMBER
                ]
            );
        $stub = new LogAndThrowExceptionTraitUsingClassStub($logger);
        $stub->xthrowErrorException(
            \RuntimeException::class,
            'Some message with {value1} and {value2}.',
            ['value1' => 'data1', 'value2' => 'data2'],
            null,
            12345
        );
    }
    public function testThrowCriticalException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Some message with data1 and data2.');
        $this->expectExceptionCode(12345);

        $previousException = new \UnexpectedValueException('Unexpected value.');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(static::once())
            ->method('critical')
            ->with(
                'Some message with {value1} and {value2}.',
                [
                    'value1' => 'data1',
                    'value2' => 'data2',
                    'called_in' => __DIR__ . \DIRECTORY_SEPARATOR . 'LogAndThrowExceptionTraitUsingClassStub.php'
                        . ':' . LogAndThrowExceptionTraitUsingClassStub::THROW_CRITICAL_LINE_NUMBER,
                    'exception' => $previousException
                ]
            );
        $stub = new LogAndThrowExceptionTraitUsingClassStub($logger);
        $stub->xthrowCriticalException(
            \RuntimeException::class,
            'Some message with {value1} and {value2}.',
            ['value1' => 'data1', 'value2' => 'data2'],
            $previousException,
            12345
        );
    }

    public function testThrowCriticalExceptionWithoutLogger(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Some message with data1 and data2.');
        $this->expectExceptionCode(12345);

        $previousException = new \UnexpectedValueException('Unexpected value.');

        $stub = new LogAndThrowExceptionTraitUsingClassStub(null);
        $stub->xthrowCriticalException(
            \RuntimeException::class,
            'Some message with {value1} and {value2}.',
            ['value1' => 'data1', 'value2' => 'data2'],
            $previousException,
            12345
        );
    }

    public function testThrowCriticalExceptionWithoutPreviousException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Some message with data1 and data2.');
        $this->expectExceptionCode(12345);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(static::once())
            ->method('critical')
            ->with(
                'Some message with {value1} and {value2}.',
                [
                    'value1' => 'data1',
                    'value2' => 'data2',
                    'called_in' => __DIR__ . \DIRECTORY_SEPARATOR . 'LogAndThrowExceptionTraitUsingClassStub.php'
                        . ':' . LogAndThrowExceptionTraitUsingClassStub::THROW_CRITICAL_LINE_NUMBER
                ]
            );
        $stub = new LogAndThrowExceptionTraitUsingClassStub($logger);
        $stub->xthrowCriticalException(
            \RuntimeException::class,
            'Some message with {value1} and {value2}.',
            ['value1' => 'data1', 'value2' => 'data2'],
            null,
            12345
        );
    }
}
