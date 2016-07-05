<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Delete;

use Oro\Bundle\ApiBundle\Processor\Delete\ValidateDeletionResult;

class ValidateDeletionResultTest extends DeleteProcessorTestCase
{
    /** @var ValidateDeletionResult */
    protected $processor;

    public function setUp()
    {
        parent::setUp();

        $this->processor = new ValidateDeletionResult();
    }

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The record was not deleted.
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
