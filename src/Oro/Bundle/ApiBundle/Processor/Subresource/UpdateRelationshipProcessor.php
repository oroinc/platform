<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource;

use Oro\Bundle\ApiBundle\Processor\Subresource\UpdateRelationship\UpdateRelationshipContext;

class UpdateRelationshipProcessor extends SubresourceProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function createContextObject()
    {
        return new UpdateRelationshipContext($this->configProvider, $this->metadataProvider);
    }
}
