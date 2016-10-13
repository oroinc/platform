<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

/**
 * Adds human-readable descriptions for the primary field of the entity.
 */
class CompleteDesctiptionForPrimaryFields implements ProcessorInterface
{
    const PRIMARY_FIELD_DESCRIPTION = 'The identifier of an entity';

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
            return;
        }

        $identifierFieldName = reset($identifierFieldNames);
        if ($definition->hasField($identifierFieldName)) {
            $primaryField = $definition->getField($identifierFieldName);
            if (empty($primaryField->getDescription())) {
                $primaryField->setDescription(self::PRIMARY_FIELD_DESCRIPTION);
            }
        }
    }
}
