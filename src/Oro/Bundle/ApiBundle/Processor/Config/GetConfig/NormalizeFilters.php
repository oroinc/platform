<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class NormalizeFilters extends NormalizeChildSection
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

        $definition = $context->getResult();
        if (null === $definition) {
            // an entity configuration does not exist
            return;
        }

        $this->collect($filters, ConfigUtil::FILTERS, $definition);
        $context->setFilters($filters);
    }
}
