<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Model\Label;

/**
 * Localizes "label" and "description" attributes for fields.
 */
class NormalizeDescriptionForFields extends NormalizeDescription
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

        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            $label = $field->getLabel();
            if ($label instanceof Label) {
                $field->setLabel($this->trans($label));
            }

            $description = $field->getDescription();
            if ($description instanceof Label) {
                $field->setDescription($this->trans($description));
            }
        }
    }
}
