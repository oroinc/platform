<?php

namespace Oro\Bundle\ApiBundle\Processor;

/**
 * The main processor for "unhandled_error" action.
 */
class UnhandledErrorProcessor extends RequestActionProcessor
{
    /**
     * {@inheritDoc}
     */
    protected function createContextObject(): Context
    {
        return new Context($this->configProvider, $this->metadataProvider);
    }
}
