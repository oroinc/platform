<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;

/**
 * The main processor for "get_list" action.
 */
class GetListProcessor extends RequestActionProcessor
{
    /**
     * {@inheritDoc}
     */
    protected function createContextObject(): GetListContext
    {
        return new GetListContext($this->configProvider, $this->metadataProvider);
    }
}
