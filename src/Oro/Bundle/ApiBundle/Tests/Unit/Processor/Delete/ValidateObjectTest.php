<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Delete;

use Oro\Bundle\ApiBundle\Processor\Delete\ValidateObject;

class ValidateObjectTest extends DeleteContextTestCase
{
    /** @var ValidateObject */
    protected $processor;

    public function setUp()
    {
        $this->processor = new ValidateObject();
        parent::setUp();
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
}
