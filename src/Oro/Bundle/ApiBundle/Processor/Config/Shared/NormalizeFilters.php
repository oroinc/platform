<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Removes all filters marked as excluded.
 * Updates the property path attribute for existing filters.
 * Extracts filters from the definitions of related entities.
 * Removes filters by identifier field if they duplicate a filter by related entity.
 * For example if both "product" and "product.id" filters exist, the "product.id" filter will be removed.
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
