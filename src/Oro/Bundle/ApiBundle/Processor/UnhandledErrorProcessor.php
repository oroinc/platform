<?php

namespace Oro\Bundle\ApiBundle\Processor;

/**
 * The main processor for "unhandled_error" action.
 */
class UnhandledErrorProcessor extends RequestActionProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function createContextObject()
    {
        return new Context($this->configProvider, $this->metadataProvider);
    }
}
