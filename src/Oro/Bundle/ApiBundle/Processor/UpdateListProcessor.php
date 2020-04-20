<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Processor\UpdateList\UpdateListContext;

/**
 * The main processor for "update_list" action.
 */
class UpdateListProcessor extends RequestActionProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function createContextObject()
    {
        return new UpdateListContext($this->configProvider, $this->metadataProvider);
    }
}
