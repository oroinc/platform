<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Excludes all fields that were not configured explicitly in in "Resources/config/oro/api.yml".
 */
class UpdateFieldExclusions implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $explicitlyConfiguredFields = array_fill_keys($context->getExplicitlyConfiguredFieldNames(), true);
        if (!$explicitlyConfiguredFields) {
            return;
        }

        $fields = $context->getResult()->getFields();
        foreach ($fields as $fieldName => $field) {
            if (!isset($explicitlyConfiguredFields[$fieldName]) && !$field->hasExcluded()) {
                $field->setExcluded();
            }
        }
    }
}
