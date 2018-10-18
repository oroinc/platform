<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Makes sure that the identifier of an entity exists in the context.
 * This validation is skipped if an entity does not have an identifier.
 */
class ValidateEntityIdExists implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SingleItemContext $context */

        $entityId = $context->getId();
        if (empty($entityId) && $context->hasIdentifierFields()) {
            $context->addError(
                Error::createValidationError(
                    Constraint::ENTITY_ID,
                    'The identifier of an entity must be set in the context.'
                )
            );
        }
    }
}
