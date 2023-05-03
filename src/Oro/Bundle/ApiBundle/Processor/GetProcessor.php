<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Processor\Get\GetContext;

/**
 * The main processor for "get" action.
 */
class GetProcessor extends RequestActionProcessor
{
    /**
     * {@inheritDoc}
     */
    protected function createContextObject(): GetContext
    {
        return new GetContext($this->configProvider, $this->metadataProvider);
    }
}
