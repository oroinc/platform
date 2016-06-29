<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Shared\ValidateEntityIdExists;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;

class ValidateEntityIdExistsTest extends GetProcessorTestCase
{
    /** @var ValidateEntityIdExists */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new ValidateEntityIdExists();
    }

    public function testProcess()
    {
        $this->context->setId(123);
        $this->processor->process($this->context);
    }

    public function testProcessWhenNoId()
    {
        $this->processor->process($this->context);

        $this->assertEquals(
            [
                Error::createValidationError(
                    'entity identifier constraint',
                    'The identifier of an entity must be set in the context.'
                )
            ],
            $this->context->getErrors()
        );
    }
}
