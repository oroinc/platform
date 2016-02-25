<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\ApiBundle\Model\Label;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

/**
 * Adds "description" attribute for filters.
 */
class SetDescriptionForFilters extends SetDescription
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

        $entityClass = $context->getClassName();
        if (!$this->entityConfigProvider->hasConfig($entityClass)) {
            // only configurable entities are supported
            return;
        }

        $fields = $filters->getFields();
        foreach ($fields as $filterKey => $filterConfig) {
            if (!$filterConfig->hasDescription()) {
                $config = $this->findFieldConfig($entityClass, $filterKey, $filterConfig);
                if (null !== $config) {
                    $filterConfig->setDescription(new Label($config->get('label')));
                }
            }
        }
    }
}
