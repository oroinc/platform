<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\AssertResultDataIsArray;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;

class AssertResultDataIsArrayTest extends GetListProcessorTestCase
{
    /** @var AssertResultDataIsArray */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new AssertResultDataIsArray();
    }

    public function testProcessWhenResultDoesNotExist()
    {
        $this->processor->process($this->context);
    }

    public function testProcessWhenResultResultDataDoesNotExist()
    {
        $this->context->setResult([]);
        $this->processor->process($this->context);
    }

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The "data" section must be an array.
     */
    public function testProcessWhenResultResultDataIsNotArray()
    {
        $this->context->setResult(['data' => 123]);
        $this->processor->process($this->context);
    }

    public function testProcessWhenResultResultDataIsArray()
    {
        $this->context->setResult(['data' => []]);
        $this->processor->process($this->context);
    }
}
