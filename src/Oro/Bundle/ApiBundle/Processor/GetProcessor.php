<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Processor\Get\GetContext;

/**
 * The main processor for "get" action.
 */
class GetProcessor extends RequestActionProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function createContextObject()
    {
        return new GetContext($this->configProvider, $this->metadataProvider);
    }
}
