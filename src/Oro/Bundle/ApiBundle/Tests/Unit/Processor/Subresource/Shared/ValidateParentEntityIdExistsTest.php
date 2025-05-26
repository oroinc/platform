<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\ValidateParentEntityIdExists;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorTestCase;

class ValidateParentEntityIdExistsTest extends GetSubresourceProcessorTestCase
{
    private ValidateParentEntityIdExists $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new ValidateParentEntityIdExists();
    }

    public function testProcessWhenParentEntityIdExists(): void
    {
        $this->context->setParentId(123);
        $this->processor->process($this->context);

        self::assertCount(0, $this->context->getErrors());
    }

    public function testProcessWhenParentEntityIdDoesNotExist(): void
    {
        $this->processor->process($this->context);

        self::assertEquals(
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
