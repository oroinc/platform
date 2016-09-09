<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Processor\DeleteList\DeleteListContext;

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
