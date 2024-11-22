<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\DisableParentEntityTypeAccessValidation;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\ValidateParentEntityTypeAccess;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorTestCase;

class DisableParentEntityTypeAccessValidationTest extends GetSubresourceProcessorTestCase
{
    public function testProcess(): void
    {
        $processor = new DisableParentEntityTypeAccessValidation('VIEW');
        $processor->process($this->context);
        self::assertTrue($this->context->isProcessed(ValidateParentEntityTypeAccess::getOperationName('VIEW')));
    }
}
