<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;

/**
 * The main processor for "get_list" action.
 */
class GetListProcessor extends RequestActionProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function createContextObject()
    {
        return new GetListContext($this->configProvider, $this->metadataProvider);
    }
}
