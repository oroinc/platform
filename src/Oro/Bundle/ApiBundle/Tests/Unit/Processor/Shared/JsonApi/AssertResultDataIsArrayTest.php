<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\AssertResultDataIsArray;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;

class AssertResultDataIsArrayTest extends GetListProcessorTestCase
{
    private AssertResultDataIsArray $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new AssertResultDataIsArray();
    }

    public function testProcessWhenResultDoesNotExist(): void
    {
        $this->processor->process($this->context);
    }

    public function testProcessWhenResultResultDataDoesNotExist(): void
    {
        $this->context->setResult([]);
        $this->processor->process($this->context);
    }

    public function testProcessWhenResultResultDataIsNotArray(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The "data" section must be an array.');

        $this->context->setResult(['data' => 123]);
        $this->processor->process($this->context);
    }

    public function testProcessWhenResultResultDataIsArray(): void
    {
        $this->context->setResult(['data' => []]);
        $this->processor->process($this->context);
    }
}
