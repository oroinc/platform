<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Processor\Delete\DeleteContext;

/**
 * The main processor for "delete" action.
 */
class DeleteProcessor extends RequestActionProcessor
{
    /**
     * {@inheritDoc}
     */
    protected function createContextObject(): DeleteContext
    {
        return new DeleteContext($this->configProvider, $this->metadataProvider);
    }
}
