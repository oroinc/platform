<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\ApiBundle\Model\Label;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\NormalizeDescription;

/**
 * Localizes "label", "plural_label" and "description" attributes for the entity.
 */
class NormalizeDescriptionForEntity extends NormalizeDescription
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

        $label = $definition->getLabel();
        if ($label instanceof Label) {
            $definition->setLabel($this->trans($label));
        }

        $pluralLabel = $definition->getPluralLabel();
        if ($pluralLabel instanceof Label) {
            $definition->setPluralLabel($this->trans($pluralLabel));
        }

        $description = $definition->getDescription();
        if ($description instanceof Label) {
            $definition->setDescription($this->trans($description));
        }
    }
}
