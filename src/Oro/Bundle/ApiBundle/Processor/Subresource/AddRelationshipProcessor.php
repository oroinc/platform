<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource;

use Oro\Bundle\ApiBundle\Processor\Subresource\AddRelationship\AddRelationshipContext;

/**
 * The main processor for "add_relationship" action.
 */
class AddRelationshipProcessor extends SubresourceProcessor
{
    /**
     * {@inheritDoc}
     */
    protected function createContextObject(): AddRelationshipContext
    {
        return new AddRelationshipContext($this->configProvider, $this->metadataProvider);
    }
}
