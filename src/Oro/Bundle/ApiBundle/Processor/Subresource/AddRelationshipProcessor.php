<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource;

use Oro\Bundle\ApiBundle\Processor\RequestActionProcessor;
use Oro\Bundle\ApiBundle\Processor\Subresource\AddRelationship\AddRelationshipContext;

class AddRelationshipProcessor extends RequestActionProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function createContextObject()
    {
        return new AddRelationshipContext($this->configProvider, $this->metadataProvider);
    }
}
