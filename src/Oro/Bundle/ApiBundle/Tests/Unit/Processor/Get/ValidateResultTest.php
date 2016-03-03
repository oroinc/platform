<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get;

use Oro\Bundle\ApiBundle\Processor\Get\ValidateResult;

class ValidateResultTest extends GetProcessorTestCase
{
    /** @var ValidateResult */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new ValidateResult();
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @expectedExceptionMessage Unsupported request.
     */
    public function testProcessOnEmptyResult()
    {
        $this->processor->process($this->context);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @expectedExceptionMessage An entity with the requested identifier does not exist.
     */
    public function testProcessOnNullResult()
    {
        $this->context->setResult(null);
        $this->processor->process($this->context);
    }
}
