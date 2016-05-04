<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\ActionProcessor;
use Oro\Bundle\ApiBundle\Processor\CollectResources\CollectResourcesContext;

class CollectResourcesProcessor extends ActionProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function createContextObject()
    {
        return new CollectResourcesContext();
    }
}
