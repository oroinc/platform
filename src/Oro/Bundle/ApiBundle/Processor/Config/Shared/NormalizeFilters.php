<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Removes all filters marked as excluded.
 * Updates the property path attribute for existing filters.
 * Extracts filters from the definitions of related entities.
 */
class NormalizeFilters extends NormalizeSection
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $this->normalize(
            $context->getFilters(),
            ConfigUtil::FILTERS,
            $context->getClassName(),
            $context->getResult()
        );
    }
}
