<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

/**
 * Adds human-readable descriptions for the createdAt and updatedAt fields of the entity.
 */
class CompleteDescriptionForCreatedUpdatedFields implements ProcessorInterface
{
    const CREATED_AT_FIELD_NAME  = 'createdAt';
    const CREATED_AT_DESCRIPTION = 'The date and time of resource record creation';
    const UPDATED_AT_FIELD_NAME  = 'updatedAt';
    const UPDATED_AT_DESCRIPTION = 'The date and time of the last update of the resource record';

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

        if ($definition->hasField(self::CREATED_AT_FIELD_NAME)) {
            $createdAtField = $definition->getField(self::CREATED_AT_FIELD_NAME);
            if (empty($createdAtField->getDescription())) {
                $createdAtField->setDescription(self::CREATED_AT_DESCRIPTION);
            }
        }

        if ($definition->hasField(self::UPDATED_AT_FIELD_NAME)) {
            $updatedAtField = $definition->getField(self::UPDATED_AT_FIELD_NAME);
            if (empty($updatedAtField->getDescription())) {
                $updatedAtField->setDescription(self::UPDATED_AT_DESCRIPTION);
            }
        }
    }
}
