<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\ValidateParentEntityIdExists;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorTestCase;

class ValidateParentEntityIdExistsTest extends GetSubresourceProcessorTestCase
{
    /** @var ValidateParentEntityIdExists */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new ValidateParentEntityIdExists();
    }

    public function testProcessWhenParentEntityIdExists()
    {
        $this->context->setParentId(123);
        $this->processor->process($this->context);
    }

    public function testProcessWhenParentEntityIdDoesNotExist()
    {
        $this->processor->process($this->context);

        $this->assertEquals(
            [
                Error::createValidationError(
                    'entity identifier constraint',
                    'The identifier of the parent entity must be set in the context.'
                )
            ],
            $this->context->getErrors()
        );
    }
}
