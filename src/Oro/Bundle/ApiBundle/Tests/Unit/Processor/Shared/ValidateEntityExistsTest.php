<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Shared\ValidateEntityExists;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;

class ValidateEntityExistsTest extends GetProcessorTestCase
{
    /** @var ValidateEntityExists */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new ValidateEntityExists();
    }

    public function testProcessWhenEntityIsLoaded()
    {
        $this->context->setResult(new \stdClass());
        $this->processor->process($this->context);
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
}
