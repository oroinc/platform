<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\DisableParentEntityObjectAccessValidation;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\ValidateParentEntityObjectAccess;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeRelationshipProcessorTestCase;

class DisableParentEntityObjectAccessValidationTest extends ChangeRelationshipProcessorTestCase
{
    public function testProcess(): void
    {
        $processor = new DisableParentEntityObjectAccessValidation('VIEW');
        $processor->process($this->context);
        self::assertTrue($this->context->isProcessed(ValidateParentEntityObjectAccess::getOperationName('VIEW')));
    }
}
