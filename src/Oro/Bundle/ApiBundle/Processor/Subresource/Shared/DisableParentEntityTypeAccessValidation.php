<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Disables the validation of an access to the type of entities specified
 * in the "parentClass" property of the context is granted.
 * The permission type is provided in $permission argument of the class constructor.
 */
class DisableParentEntityTypeAccessValidation implements ProcessorInterface
{
    public function __construct(
        private readonly string $permission
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var SubresourceContext $context */

        $context->setProcessed(ValidateParentEntityTypeAccess::getOperationName($this->permission));
    }
}
