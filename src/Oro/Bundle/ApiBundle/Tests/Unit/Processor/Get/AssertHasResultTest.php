<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get;

use Oro\Bundle\ApiBundle\Processor\Get\AssertHasResult;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AssertHasResultTest extends GetProcessorTestCase
{
    private AssertHasResult $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new AssertHasResult();
    }

    public function testProcessOnExpectedResult(): void
    {
        $this->context->setResult(['key' => 'value']);
        $this->processor->process($this->context);
    }

    public function testProcessOnEmptyResult(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unsupported request.');

        $this->processor->process($this->context);
    }

    public function testProcessOnNullResult(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('An entity with the requested identifier does not exist.');

        $this->context->setResult(null);
        $this->processor->process($this->context);
    }
}
