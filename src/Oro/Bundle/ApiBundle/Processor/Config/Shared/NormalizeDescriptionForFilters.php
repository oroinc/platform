<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class NormalizeDescriptionForFilters extends NormalizeDescription
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $filters = $context->getFilters();
        if (empty($filters) || empty($filters[ConfigUtil::FIELDS])) {
            // a configuration of filters does not exist
            return;
        }

        foreach ($filters[ConfigUtil::FIELDS] as &$filterConfig) {
            $this->normalizeAttribute($filterConfig, ConfigUtil::DESCRIPTION);
        }

        $context->setFilters($filters);
    }
}
