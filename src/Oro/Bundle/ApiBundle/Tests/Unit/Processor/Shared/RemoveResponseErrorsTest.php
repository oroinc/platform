<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Shared\RemoveResponseErrors;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Symfony\Component\HttpFoundation\Response;

class RemoveResponseErrorsTest extends GetListProcessorTestCase
{
    private RemoveResponseErrors $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new RemoveResponseErrors();
    }

    public function testProcessWhenStatusCodeIsNotSet(): void
    {
        $this->context->addError(Error::create('some error'));
        $this->processor->process($this->context);

        self::assertTrue($this->context->hasErrors());
    }

    public function testProcessWhenNoErrors(): void
    {
        $this->context->setResponseStatusCode(Response::HTTP_OK);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWhenResponseCanContainErrorsInContent(): void
    {
        $this->context->setResponseStatusCode(Response::HTTP_BAD_REQUEST);
        $this->context->addError(Error::create('some error'));
        $this->processor->process($this->context);

        self::assertTrue($this->context->hasErrors());
    }

    public function testProcessWhenResponseShouldNotContainErrorsInContent(): void
    {
        $this->context->setResponseStatusCode(Response::HTTP_METHOD_NOT_ALLOWED);
        $this->context->addError(Error::create('some error'));
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }
}
