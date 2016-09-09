<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource;

use Oro\Bundle\ApiBundle\Processor\RequestActionProcessor;
use Oro\Bundle\ApiBundle\Processor\Subresource\DeleteRelationship\DeleteRelationshipContext;

class DeleteRelationshipProcessor extends RequestActionProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function createContextObject()
    {
        return new DeleteRelationshipContext($this->configProvider, $this->metadataProvider);
    }
}
