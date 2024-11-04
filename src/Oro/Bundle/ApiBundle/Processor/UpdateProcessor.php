<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Processor\Update\UpdateContext;

/**
 * The main processor for "update" action.
 */
class UpdateProcessor extends RequestActionProcessor
{
    #[\Override]
    protected function createContextObject(): UpdateContext
    {
        return new UpdateContext($this->configProvider, $this->metadataProvider);
    }
}
