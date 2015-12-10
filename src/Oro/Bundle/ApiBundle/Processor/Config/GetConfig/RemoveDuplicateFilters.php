<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

class RemoveDuplicateFilters extends RemoveDuplicates
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $filters = $context->getFilters();
        if (null === $filters) {
            // a filters' configuration does not exist
            return;
        }

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $context->setFilters($this->removeDuplicates($filters, $entityClass));
    }
}
