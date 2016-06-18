<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\ValidateParentClassNameExists;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorTestCase;

class ValidateParentClassNameExistsTest extends GetSubresourceProcessorTestCase
{
    /** @var ValidateParentClassNameExists */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new ValidateParentClassNameExists();
    }

    public function testProcessWhenParentClassNameExists()
    {
        $this->context->setParentClassName('Test\Class');
        $this->processor->process($this->context);
    }

    public function testProcessWhenParentClassNameDoesNotExist()
    {
        $this->processor->process($this->context);

        $this->assertEquals(
            [
                Error::createValidationError(
                    'entity type constraint',
                    'The parent entity class must be set in the context.'
                )
            ],
            $this->context->getErrors()
        );
    }
}
