<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Disables the validation of an access to the parent entity object is granted.
 * The permission type is provided in $permission argument of the class constructor.
 */
class DisableParentEntityObjectAccessValidation implements ProcessorInterface
{
    public function __construct(
        private readonly string $permission
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var SubresourceContext $context */

        $context->setProcessed(ValidateParentEntityObjectAccess::getOperationName($this->permission));
    }
}
