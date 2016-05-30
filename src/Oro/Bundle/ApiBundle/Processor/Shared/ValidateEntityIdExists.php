<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\Constraint;

/**
 * Makes sure that the identifier of an entity exists in the Context.
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
        if (empty($entityId)) {
            $context->addError(
                Error::createValidationError(
                    Constraint::ENTITY_ID,
                    'The identifier of an entity must be set in the context.'
                )
            );
        }
    }
}
