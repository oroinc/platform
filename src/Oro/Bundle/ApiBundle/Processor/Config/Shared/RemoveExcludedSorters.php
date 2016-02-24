<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

/**
 * Removes all sorters marked as excluded.
 */
class RemoveExcludedSorters extends RemoveExclusions
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $this->removeExcludedFields($context->getSorters());
    }
}
