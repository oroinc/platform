<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Shared\ValidateRequestTypeExists;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;

class ValidateRequestTypeExistsTest extends GetListProcessorTestCase
{
    /** @var ValidateRequestTypeExists */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new ValidateRequestTypeExists();
    }

    public function testProcess()
    {
        $this->processor->process($this->context);
    }

    public function testProcessWhenNoRequestType()
    {
        $this->context->getRequestType()->clear();
        $this->processor->process($this->context);

        $this->assertEquals(
            [
                Error::createValidationError(
                    'request type constraint',
                    'The type of a request must be set in the context.'
                )
            ],
            $this->context->getErrors()
        );
    }
}
