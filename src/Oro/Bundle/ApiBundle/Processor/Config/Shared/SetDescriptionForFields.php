<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\ApiBundle\Model\Label;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

/**
 * Adds "label" attribute for fields.
 */
class SetDescriptionForFields extends SetDescription
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        if (!$definition->hasFields()) {
            // nothing to process
            return;
        }

        $entityClass = $context->getClassName();
        if (!$this->entityConfigProvider->hasConfig($entityClass)) {
            // only configurable entities are supported
            return;
        }

        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            if (!$field->hasLabel()) {
                $config = $this->findFieldConfig($entityClass, $fieldName, $field);
                if (null !== $config) {
                    $field->setLabel(new Label($config->get('label')));
                }
            }
        }
    }
}
