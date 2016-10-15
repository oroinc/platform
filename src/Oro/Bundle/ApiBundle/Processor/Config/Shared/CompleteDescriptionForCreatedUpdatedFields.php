<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

/**
 * Adds human-readable descriptions for the createdAt and updatedAt fields of the entity.
 */
class CompleteDescriptionForCreatedUpdatedFields implements ProcessorInterface
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
        $this->updateFieldDescription(
            $definition,
            'createdAt',
            'The date and time of resource record creation'
        );
        $this->updateFieldDescription(
            $definition,
            'updatedAt',
            'The date and time of the last update of the resource record'
        );
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $fieldName
     * @param string                 $description
     */
    protected function updateFieldDescription(EntityDefinitionConfig $definition, $fieldName, $description)
    {
        $field = $definition->getField($fieldName);
        if (null !== $field) {
            $existingDescription = $field->getDescription();
            if (empty($existingDescription)) {
                $field->setDescription($description);
            }
        }
    }
}
