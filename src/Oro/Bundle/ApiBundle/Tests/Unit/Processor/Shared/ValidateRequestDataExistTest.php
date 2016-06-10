<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
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

    public function testProcessOnNotExistingData()
    {
        $this->processor->process($this->context);
        $this->assertEquals(
            [Error::createValidationError('request data constraint', 'The request data should not be empty.')],
            $this->context->getErrors()
        );
    }

    public function testProcessOnEmptyData()
    {
        $this->context->setRequestData([]);
        $this->processor->process($this->context);
        $this->assertEquals(
            [Error::createValidationError('request data constraint', 'The request data should not be empty.')],
            $this->context->getErrors()
        );
    }

    public function testProcessWithData()
    {
        $this->context->setRequestData(['a' => 'b']);
        $this->processor->process($this->context);
        $this->assertFalse($this->context->hasErrors());
    }
}
