<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Processor\Update\UpdateContext;

/**
 * The main processor for "update" action.
 */
class UpdateProcessor extends RequestActionProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function createContextObject()
    {
        return new UpdateContext($this->configProvider, $this->metadataProvider);
    }
}
