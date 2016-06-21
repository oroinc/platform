<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Shared\ValidateNormalizedResultExists;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;

class ValidateNormalizedResultExistsTest extends GetProcessorTestCase
{
    /** @var ValidateNormalizedResultExists */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new ValidateNormalizedResultExists();
    }

    public function testProcessWhenResultExists()
    {
        $this->context->setResult([]);
        $this->processor->process($this->context);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The result does not exist.
     */
    public function testProcessWhenResultDoesNotExist()
    {
        $this->processor->process($this->context);
    }
}
