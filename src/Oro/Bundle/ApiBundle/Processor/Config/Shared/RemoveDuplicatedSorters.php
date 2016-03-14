<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

/**
 * Removes sorters by identifier field if they duplicate a sorter by related entity.
 * For example if both "product" and "product.id" sorters exist, the "product.id" sorter will be removed.
 */
class RemoveDuplicatedSorters extends RemoveDuplicates
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $this->removeDuplicatedFields($context->getSorters(), $context->getClassName());
    }
}
