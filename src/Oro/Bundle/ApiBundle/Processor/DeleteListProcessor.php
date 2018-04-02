<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Processor\DeleteList\DeleteListContext;

/**
 * The main processor for "delete_list" action.
 */
class DeleteListProcessor extends RequestActionProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function createContextObject()
    {
        return new DeleteListContext($this->configProvider, $this->metadataProvider);
    }
}
