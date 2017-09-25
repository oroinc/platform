<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource;

use Oro\Bundle\ApiBundle\Processor\Subresource\AddRelationship\AddRelationshipContext;

class AddRelationshipProcessor extends SubresourceProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function createContextObject()
    {
        return new AddRelationshipContext($this->configProvider, $this->metadataProvider);
    }
}
