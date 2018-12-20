<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Shared\AssertNotHasResult;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;

class AssertNotHasResultTest extends GetProcessorTestCase
{
    /** @var AssertNotHasResult */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new AssertNotHasResult();
    }

    public function testProcessWhenNoResult()
    {
        $this->processor->process($this->context);
    }

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The result should not exist.
     */
    public function testProcessWhenHasResult()
    {
        $this->context->setResult(new \stdClass());
        $this->processor->process($this->context);
    }
}
