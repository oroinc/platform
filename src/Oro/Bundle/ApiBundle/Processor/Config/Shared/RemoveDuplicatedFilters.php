<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

/**
 * Removes filters by identifier field if they duplicate a filter by related entity.
 * For example if both "product" and "product.id" filters exist, the "product.id" filter will be removed.
 */
class RemoveDuplicatedFilters extends RemoveDuplicates
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $this->removeDuplicatedFields($context->getFilters(), $context->getClassName());
    }
}
