<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Shared\ValidateRequestDataExist;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class ValidateRequestDataExistTest extends FormProcessorTestCase
{
    /** @var ValidateRequestDataExist */
    protected $processor;

    public function setUp()
    {
        parent::setUp();
        $this->processor = new ValidateRequestDataExist();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Request must have data.
     */
    public function testProcessOnNotExistingData()
    {
        $this->processor->process($this->context);
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

    public function testProcessWithData()
    {
        $this->context->setRequestData(['a' => 'b']);
        $this->processor->process($this->context);
    }
}
