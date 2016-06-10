<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Request\Constraint;

/**
 * Makes sure that the association name exists in the Context.
 */
class ValidateAssociationNameExists implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SubresourceContext $context */

        $associationName = $context->getAssociationName();
        if (!$associationName) {
            $context->addError(
                Error::createValidationError(
                    Constraint::RELATIONSHIP,
                    'The association name must be set in the context.'
                )
            );
        }
    }
}
