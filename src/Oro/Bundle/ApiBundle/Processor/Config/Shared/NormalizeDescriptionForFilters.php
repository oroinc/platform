<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Model\Label;

/**
 * Localizes "description" attribute for filters.
 */
class NormalizeDescriptionForFilters extends NormalizeDescription
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $filters = $context->getFilters();
        if (!$filters->hasFields()) {
            // nothing to process
            return;
        }

        $fields = $filters->getFields();
        foreach ($fields as $fieldName => $field) {
            $description = $field->getDescription();
            if ($description instanceof Label) {
                $field->setDescription($this->trans($description));
            }
        }
    }
}
