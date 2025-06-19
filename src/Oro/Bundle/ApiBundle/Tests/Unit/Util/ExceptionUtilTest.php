<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Oro\Bundle\ApiBundle\Util\ExceptionUtil;
use Oro\Component\ChainProcessor\Exception\ExecutionFailedException;
use PHPUnit\Framework\TestCase;

class ExceptionUtilTest extends TestCase
{
    public function testGetProcessorUnderlyingExceptionWithoutInnerException(): void
    {
        $exception = new \InvalidArgumentException();

        self::assertSame(
            $exception,
            ExceptionUtil::getProcessorUnderlyingException($exception)
        );
    }

    public function testGetProcessorUnderlyingExceptionWithoutExecutionFailedAsInnerException(): void
    {
        $exception = new \LogicException(
            'test',
            0,
            new \InvalidArgumentException()
        );

        self::assertSame(
            $exception,
            ExceptionUtil::getProcessorUnderlyingException($exception)
        );
    }

    public function testGetProcessorUnderlyingExceptionForExecutionFailedAsRootException(): void
    {
        $innerException = new \InvalidArgumentException();
        $exception = new ExecutionFailedException(
            'processor1',
            null,
            null,
            $innerException
        );

        self::assertSame(
            $innerException,
            ExceptionUtil::getProcessorUnderlyingException($exception)
        );
    }

    public function testGetProcessorUnderlyingExceptionForExecutionFailedAsInnerException(): void
    {
        $innerException = new \InvalidArgumentException();
        $executionFailedException = new ExecutionFailedException(
            'processor1',
            null,
            null,
            $innerException
        );
        $exception = new \LogicException(
            'test',
            0,
            $executionFailedException
        );

        self::assertSame(
            $exception,
            ExceptionUtil::getProcessorUnderlyingException($exception)
        );
    }
}
