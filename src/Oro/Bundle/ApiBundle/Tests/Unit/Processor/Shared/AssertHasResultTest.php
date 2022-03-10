<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\Shared\AssertHasResult;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use Symfony\Component\HttpFoundation\Response;

class AssertHasResultTest extends GetProcessorTestCase
{
    /** @var AssertHasResult */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new AssertHasResult();
    }

    public function testProcessWhenResultExists()
    {
        $this->context->setResult([]);
        $this->processor->process($this->context);
    }

    public function testProcessWhenResultDoesNotExistAndNoResponseStatusCode()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The result does not exist.');

        $this->processor->process($this->context);
    }

    public function testProcessWhenResultDoesNotExistAndResponseShouldHaveContext()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The result does not exist.');

        $this->context->setResponseStatusCode(Response::HTTP_BAD_REQUEST);
        $this->processor->process($this->context);
    }

    public function testProcessWhenResultDoesNotExistAndResponseShouldNotHaveContext()
    {
        $this->context->setResponseStatusCode(Response::HTTP_METHOD_NOT_ALLOWED);
        $this->processor->process($this->context);
    }
}
