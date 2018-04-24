<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
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
    public function process(ContextInterface $context)
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

        if (!$hasAssociations) {
            $definition->disableInclusion();
        }
    }
}
