<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Shared\ValidateLoadedEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;

class ValidateLoadedEntityTest extends GetProcessorTestCase
{
    /** @var ValidateLoadedEntity */
    protected $processor;

    public function setUp()
    {
        parent::setUp();

        $this->processor = new ValidateLoadedEntity();
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @expectedExceptionMessage An entity with the requested identifier does not exist.
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
