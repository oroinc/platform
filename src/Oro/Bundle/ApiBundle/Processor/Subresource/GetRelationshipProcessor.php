<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource;

use Oro\Bundle\ApiBundle\Processor\Subresource\GetRelationship\GetRelationshipContext;

/**
 * The main processor for "get_relationship" action.
 */
class GetRelationshipProcessor extends SubresourceProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function createContextObject()
    {
        return new GetRelationshipContext($this->configProvider, $this->metadataProvider);
    }
}
