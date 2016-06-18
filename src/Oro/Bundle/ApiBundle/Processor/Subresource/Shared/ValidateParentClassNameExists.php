<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Request\Constraint;

/**
 * Makes sure that the class name of the parent entity exists in the Context.
 */
class ValidateParentClassNameExists implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SubresourceContext $context */

        $parentEntityClass = $context->getParentClassName();
        if (!$parentEntityClass) {
            $context->addError(
                Error::createValidationError(
                    Constraint::ENTITY_TYPE,
                    'The parent entity class must be set in the context.'
                )
            );
        }
    }
}
