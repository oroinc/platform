<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Shared\ValidateResultNotExists;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;

class ValidateResultNotExistsTest extends GetProcessorTestCase
{
    /** @var ValidateResultNotExists */
    protected $processor;

    public function setUp()
    {
        parent::setUp();

        $this->processor = new ValidateResultNotExists();
    }

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The result should not exist.
     */
    public function testProcessOnNotDeletedEntity()
    {
        $this->context->setResult(new \stdClass());
        $this->processor->process($this->context);
    }

    /**
     * Test process without exceptions
     */
    public function testProcess()
    {
        $this->processor->process($this->context);
    }
}
