<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\UnhandledError;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\UnhandledError\ConvertExceptionToError;

class ConvertExceptionToErrorTest extends UnhandledErrorProcessorTestCase
{
    /** @var ConvertExceptionToError */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new ConvertExceptionToError();
    }

    public function testProcessWhenExceptionAlreadyProcessed(): void
    {
        $this->processor->process($this->context);
        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessForUnexpectedExceptionTypeNull(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The result should be an instance of Throwable, "null" given.');

        $this->context->setResult(null);
        $this->processor->process($this->context);
    }

    public function testProcessForUnexpectedExceptionTypeScalar(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The result should be an instance of Throwable, "string" given.');

        $this->context->setResult('test');
        $this->processor->process($this->context);
    }

    public function testProcessForUnexpectedExceptionTypeObject(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The result should be an instance of Throwable, "stdClass" given.');

        $this->context->setResult(new \stdClass());
        $this->processor->process($this->context);
    }

    public function testProcessForException(): void
    {
        $exception = new \Exception('some error');

        $this->context->setResult($exception);
        $this->processor->process($this->context);
        self::assertEquals(
            [Error::createByException($exception)],
            $this->context->getErrors()
        );
    }

    public function testProcessForThrowableButNotException(): void
    {
        $throwable = new \TypeError('some error');

        $this->context->setResult($throwable);
        $this->processor->process($this->context);
        self::assertEquals(
            [
                Error::createByException(new \ErrorException(
                    $throwable->getMessage(),
                    $throwable->getCode(),
                    E_ERROR,
                    $throwable->getFile(),
                    $throwable->getLine(),
                    $throwable
                ))
            ],
            $this->context->getErrors()
        );
    }
}
