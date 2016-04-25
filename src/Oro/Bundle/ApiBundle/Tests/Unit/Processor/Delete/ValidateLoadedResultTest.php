<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Delete;

use Oro\Bundle\ApiBundle\Processor\Delete\ValidateLoadedResult;

class ValidateLoadedResultTest extends DeleteProcessorTestCase
{
    /** @var ValidateLoadedResult */
    protected $processor;

    public function setUp()
    {
        parent::setUp();

        $this->processor = new ValidateLoadedResult();
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @expectedExceptionMessage Unsupported request.
     */
    public function testProcessWithoutObject()
    {
        $this->processor->process($this->context);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @expectedExceptionMessage An entity with the requested identifier does not exist.
     */
    public function testProcessWithNullObject()
    {
        $this->context->setResult(null);
        $this->processor->process($this->context);
    }

    /**
     * Test process without exceptions
     */
    public function testProcess()
    {
        $this->context->setResult(new \stdClass());
        $this->processor->process($this->context);
    }
}
