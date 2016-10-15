<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

/**
 * Adds human-readable description for the primary field of the entity.
 */
class CompleteDescriptionForPrimaryFields implements ProcessorInterface
{
    /**
     * @param ContextInterface $context
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $targetAction = $context->getTargetAction();
        if (!$targetAction) {
            // descriptions cannot be set for undefined target action
            return;
        }

        $definition = $context->getResult();
        $identifierFieldNames = $definition->getIdentifierFieldNames();
        if (1 !== count($identifierFieldNames)) {
            // keep descriptions for composite identifier as is
            return;
        }

        $identifierFieldName = reset($identifierFieldNames);
        if ($definition->hasField($identifierFieldName)) {
            $identifierField = $definition->getField($identifierFieldName);
            if (!$identifierField->hasDescription()) {
                $identifierField->setDescription('The identifier of an entity');
            }
        }
    }
}
