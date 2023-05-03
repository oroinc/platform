<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Processor\CollectSubresources\CollectSubresourcesContext;
use Oro\Component\ChainProcessor\ActionProcessor;

/**
 * The main processor for "collect_subresources" action.
 */
class CollectSubresourcesProcessor extends ActionProcessor
{
    /**
     * {@inheritDoc}
     */
    protected function createContextObject(): CollectSubresourcesContext
    {
        return new CollectSubresourcesContext();
    }
}
