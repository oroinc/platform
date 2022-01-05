<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Shared\RemoveEmptyUnauthorizedResponseError;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\UnhandledError\UnhandledErrorProcessorTestCase;

class RemoveEmptyUnauthorizedResponseErrorTest extends UnhandledErrorProcessorTestCase
{
    /** @var RemoveEmptyUnauthorizedResponseError */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new RemoveEmptyUnauthorizedResponseError();
    }

    public function testProcessWhenNoErrors(): void
    {
        $this->processor->process($this->context);
        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWhenNoUnauthorizedError(): void
    {
        $this->context->addError(Error::create('err1'));
        $this->context->addError(Error::create('err2', 'Error 2')->setStatusCode(400));
        $this->processor->process($this->context);
        self::assertCount(2, $this->context->getErrors());
    }

    public function testProcessForUnauthorizedErrorWithoutDetail(): void
    {
        $this->context->addError(Error::create('err1')->setStatusCode(401));
        $this->context->addError(Error::create('err2', 'Error 2')->setStatusCode(400));
        $this->processor->process($this->context);
        self::assertCount(1, $this->context->getErrors());
        self::assertEquals('err2', $this->context->getErrors()[0]->getTitle());
    }

    public function testProcessForUnauthorizedErrorWithDetail(): void
    {
        $this->context->addError(Error::create('err1', 'Error 1')->setStatusCode(401));
        $this->context->addError(Error::create('err2', 'Error 2')->setStatusCode(400));
        $this->processor->process($this->context);
        self::assertCount(2, $this->context->getErrors());
    }
}
