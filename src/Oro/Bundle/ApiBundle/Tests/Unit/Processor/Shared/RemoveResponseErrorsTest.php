<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Shared\RemoveResponseErrors;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Symfony\Component\HttpFoundation\Response;

class RemoveResponseErrorsTest extends GetListProcessorTestCase
{
    /** @var RemoveResponseErrors */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new RemoveResponseErrors();
    }

    public function testProcessWhenStatusCodeIsNotSet()
    {
        $this->context->addError(Error::create('some error'));
        $this->processor->process($this->context);

        self::assertTrue($this->context->hasErrors());
    }

    public function testProcessWhenNoErrors()
    {
        $this->context->setResponseStatusCode(Response::HTTP_OK);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWhenResponseCanContainErrorsInContent()
    {
        $this->context->setResponseStatusCode(Response::HTTP_BAD_REQUEST);
        $this->context->addError(Error::create('some error'));
        $this->processor->process($this->context);

        self::assertTrue($this->context->hasErrors());
    }

    public function testProcessWhenResponseShouldNotContainErrorsInContent()
    {
        $this->context->setResponseStatusCode(Response::HTTP_METHOD_NOT_ALLOWED);
        $this->context->addError(Error::create('some error'));
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }
}
