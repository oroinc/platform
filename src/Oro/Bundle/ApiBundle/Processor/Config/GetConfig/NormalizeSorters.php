<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class NormalizeSorters extends NormalizeChildSection
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $sorters = $context->getSorters();
        if (null === $sorters) {
            // a sorters' configuration does not exist
            return;
        }

        $definition = $context->getResult();
        if (null === $definition) {
            // an entity configuration does not exist
            return;
        }

        $this->collect($sorters, ConfigUtil::SORTERS, $definition);
        $context->setSorters($sorters);
    }
}
