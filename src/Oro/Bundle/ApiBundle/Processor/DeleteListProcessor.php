<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Processor\DeleteList\DeleteListContext;

/**
 * The main processor for "delete_list" action.
 */
class DeleteListProcessor extends RequestActionProcessor
{
    /**
     * {@inheritDoc}
     */
    protected function createContextObject(): DeleteListContext
    {
        return new DeleteListContext($this->configProvider, $this->metadataProvider);
    }
}
