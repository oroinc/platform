<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Request\Constraint;

/**
 * Makes sure that the identifier of the parent entity exists in the Context.
 */
class ValidateParentEntityIdExists implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SubresourceContext $context */

        $parentEntityId = $context->getParentId();
        if (empty($parentEntityId)) {
            $context->addError(
                Error::createValidationError(
                    Constraint::ENTITY_ID,
                    'The identifier of the parent entity must be set in the context.'
                )
            );
        }
    }
}
