<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Shared\ValidateEntityExists;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ValidateEntityExistsTest extends GetProcessorTestCase
{
    private ValidateEntityExists $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new ValidateEntityExists();
    }

    public function testProcessWhenEntityIsLoaded(): void
    {
        $this->context->setResult(new \stdClass());
        $this->processor->process($this->context);
    }

    public function testProcessWithoutObject(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('An entity with the requested identifier does not exist.');

        $this->processor->process($this->context);
    }

    public function testProcessWithNullObject(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('An entity with the requested identifier does not exist.');

        $this->context->setResult(null);
        $this->processor->process($this->context);
    }
}
