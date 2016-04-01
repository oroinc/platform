<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Shared\ValidateRequestData;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class ValidateRequestDataTest extends FormProcessorTestCase
{
    /** @var ValidateRequestData */
    protected $processor;

    public function setUp()
    {
        parent::setUp();
        $this->processor = new ValidateRequestData();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Request must have data.
     */
    public function testProcessOnEmptyData()
    {
        $this->context->setRequestData([]);
        $this->processor->process($this->context);
    }

    public function testProcessWithoutErrors()
    {
        $this->context->setRequestData(['a' => 'b']);
        $this->processor->process($this->context);
    }
}
