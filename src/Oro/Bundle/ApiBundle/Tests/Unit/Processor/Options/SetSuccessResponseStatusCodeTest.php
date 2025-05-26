<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Options;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Options\SetSuccessResponseStatusCode;
use Symfony\Component\HttpFoundation\Response;

class SetSuccessResponseStatusCodeTest extends OptionsProcessorTestCase
{
    private SetSuccessResponseStatusCode $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new SetSuccessResponseStatusCode();
    }

    public function testProcessWhenNoResultAndNoErrorsAndNoResponseStatusCode(): void
    {
        $this->processor->process($this->context);
        self::assertSame(Response::HTTP_OK, $this->context->getResponseStatusCode());
    }

    public function testProcessWhenResultIsSet(): void
    {
        $this->context->setResult([]);
        $this->processor->process($this->context);
        self::assertNull($this->context->getResponseStatusCode());
    }

    public function testProcessWhenHasErrors(): void
    {
        $this->context->addError(Error::create('test'));
        $this->processor->process($this->context);
        self::assertNull($this->context->getResponseStatusCode());
    }

    public function testProcessWhenResponseStatusCodeIsSet(): void
    {
        $existingStatusCode = 400;
        $this->context->setResponseStatusCode($existingStatusCode);
        $this->processor->process($this->context);
        self::assertSame($existingStatusCode, $this->context->getResponseStatusCode());
    }
}
