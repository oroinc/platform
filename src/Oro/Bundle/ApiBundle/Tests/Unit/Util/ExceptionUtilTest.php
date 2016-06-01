<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Oro\Bundle\ApiBundle\Util\ExceptionUtil;
use Oro\Component\ChainProcessor\Exception\ExecutionFailedException;

class ExceptionUtilTest extends \PHPUnit_Framework_TestCase
{
    public function testGetProcessorUnderlyingExceptionWithoutInnerException()
    {
        $exception = new \InvalidArgumentException();

        $this->assertSame(
            $exception,
            ExceptionUtil::getProcessorUnderlyingException($exception)
        );
    }

    public function testGetProcessorUnderlyingExceptionWithoutExecutionFailedAsInnerException()
    {
        $exception = new \LogicException(
            'test',
            0,
            new \InvalidArgumentException()
        );

        $this->assertSame(
            $exception,
            ExceptionUtil::getProcessorUnderlyingException($exception)
        );
    }

    public function testGetProcessorUnderlyingExceptionForExecutionFailedAsRootException()
    {
        $innerException = new \InvalidArgumentException();
        $exception      = new ExecutionFailedException(
            'processor1',
            null,
            null,
            $innerException
        );

        $this->assertSame(
            $innerException,
            ExceptionUtil::getProcessorUnderlyingException($exception)
        );
    }

    public function testGetProcessorUnderlyingExceptionForExecutionFailedAsInnerException()
    {
        $innerException           = new \InvalidArgumentException();
        $executionFailedException = new ExecutionFailedException(
            'processor1',
            null,
            null,
            $innerException
        );
        $exception                = new \LogicException(
            'test',
            0,
            $executionFailedException
        );

        $this->assertSame(
            $exception,
            ExceptionUtil::getProcessorUnderlyingException($exception)
        );
    }
}
