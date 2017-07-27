<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Shared\RemoveResponseErrors;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Symfony\Component\HttpFoundation\Response;

class RemoveResponseErrorsTest extends GetListProcessorTestCase
{
    /** @var RemoveResponseErrors */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new RemoveResponseErrors();
    }

    public function testProcessWhenStatusCodeIsNotSet()
    {
        $this->context->addError(Error::create('some error'));
        $this->processor->process($this->context);

        $this->assertTrue($this->context->hasErrors());
    }

    public function testProcessWhenNoErrors()
    {
        $this->context->setResponseStatusCode(Response::HTTP_OK);
        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasErrors());
    }

    public function testProcessWhenResponseCanContainErrorsInBody()
    {
        $this->context->setResponseStatusCode(Response::HTTP_BAD_REQUEST);
        $this->context->addError(Error::create('some error'));
        $this->processor->process($this->context);

        $this->assertTrue($this->context->hasErrors());
    }

    /**
     * @dataProvider getStatusCodesWithoutBody
     */
    public function testProcessWhenResponseShouldNotContainErrorsInBody($statusCode)
    {
        $this->context->setResponseStatusCode($statusCode);
        $this->context->addError(Error::create('some error'));
        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasErrors());
    }

    /**
     * @return array
     */
    public function getStatusCodesWithoutBody()
    {
        return [
            [Response::HTTP_METHOD_NOT_ALLOWED],
        ];
    }
}
