<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Processor\CollectResources\CollectResourcesContext;
use Oro\Component\ChainProcessor\ActionProcessor;

/**
 * The main processor for "collect_resources" action.
 */
class CollectResourcesProcessor extends ActionProcessor
{
    /**
     * {@inheritDoc}
     */
    protected function createContextObject(): CollectResourcesContext
    {
        return new CollectResourcesContext();
    }
}
