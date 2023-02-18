<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets the "disable_inclusion" option if it is not set yet
 * and the entity does not have associations.
 */
class CompleteDisableInclusion implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        if (!$definition->isExcludeAll() || !$definition->hasFields()) {
            // expected completed configs
            return;
        }

        if ($definition->hasDisableInclusion()) {
            // the "disable_inclusion" option is already set
            return;
        }

        if (!is_a($context->getClassName(), EntityIdentifier::class, true)
            && !$this->hasAssociations($definition)
        ) {
            $definition->disableInclusion();
        }
    }

    private function hasAssociations(EntityDefinitionConfig $definition): bool
    {
        $hasAssociations = false;
        $fields = $definition->getFields();
        foreach ($fields as $field) {
            if ($field->hasTargetEntity()
                && !$field->isExcluded()
                && !DataType::isAssociationAsField($field->getDataType())
            ) {
                $hasAssociations = true;
                break;
            }
        }

        return $hasAssociations;
    }
}
