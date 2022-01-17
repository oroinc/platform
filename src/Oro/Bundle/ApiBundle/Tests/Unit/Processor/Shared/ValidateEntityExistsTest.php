<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Shared\ValidateEntityExists;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ValidateEntityExistsTest extends GetProcessorTestCase
{
    /** @var ValidateEntityExists */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new ValidateEntityExists();
    }

    public function testProcessWhenEntityIsLoaded()
    {
        $this->context->setResult(new \stdClass());
        $this->processor->process($this->context);
    }

    public function testProcessWithoutObject()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('An entity with the requested identifier does not exist.');

        $this->processor->process($this->context);
    }

    public function testProcessWithNullObject()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('An entity with the requested identifier does not exist.');

        $this->context->setResult(null);
        $this->processor->process($this->context);
    }
}
