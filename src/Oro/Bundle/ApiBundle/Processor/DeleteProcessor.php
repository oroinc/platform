<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Processor\Delete\DeleteContext;

class DeleteProcessor extends RequestActionProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function createContextObject()
    {
        return new DeleteContext($this->configProvider, $this->metadataProvider);
    }
}
