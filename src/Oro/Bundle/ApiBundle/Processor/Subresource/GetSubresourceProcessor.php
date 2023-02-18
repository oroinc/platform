<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource;

use Oro\Bundle\ApiBundle\Processor\Subresource\GetSubresource\GetSubresourceContext;

/**
 * The main processor for "get_subresource" action.
 */
class GetSubresourceProcessor extends SubresourceProcessor
{
    /**
     * {@inheritDoc}
     */
    protected function createContextObject(): GetSubresourceContext
    {
        return new GetSubresourceContext($this->configProvider, $this->metadataProvider);
    }
}
