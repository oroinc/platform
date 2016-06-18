<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\ValidateAssociationNameExists;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorTestCase;

class ValidateAssociationNameExistsTest extends GetSubresourceProcessorTestCase
{
    /** @var ValidateAssociationNameExists */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new ValidateAssociationNameExists();
    }

    public function testProcessWhenAssociationNameExists()
    {
        $this->context->setAssociationName('test');
        $this->processor->process($this->context);
    }

    public function testProcessWhenAssociationNameDoesNotExist()
    {
        $this->processor->process($this->context);

        $this->assertEquals(
            [
                Error::createValidationError(
                    'relationship constraint',
                    'The association name must be set in the context.'
                )
            ],
            $this->context->getErrors()
        );
    }
}
