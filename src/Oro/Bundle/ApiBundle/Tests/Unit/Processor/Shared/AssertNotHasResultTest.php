<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\Shared\AssertNotHasResult;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;

class AssertNotHasResultTest extends GetProcessorTestCase
{
    /** @var AssertNotHasResult */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new AssertNotHasResult();
    }

    public function testProcessWhenNoResult()
    {
        $this->processor->process($this->context);
    }

    public function testProcessWhenHasResult()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The result should not exist.');

        $this->context->setResult(new \stdClass());
        $this->processor->process($this->context);
    }
}
