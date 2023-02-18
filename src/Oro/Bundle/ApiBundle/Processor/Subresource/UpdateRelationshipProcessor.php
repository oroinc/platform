<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource;

use Oro\Bundle\ApiBundle\Processor\Subresource\UpdateRelationship\UpdateRelationshipContext;

/**
 * The main processor for "update_relationship" action.
 */
class UpdateRelationshipProcessor extends SubresourceProcessor
{
    /**
     * {@inheritDoc}
     */
    protected function createContextObject(): UpdateRelationshipContext
    {
        return new UpdateRelationshipContext($this->configProvider, $this->metadataProvider);
    }
}
