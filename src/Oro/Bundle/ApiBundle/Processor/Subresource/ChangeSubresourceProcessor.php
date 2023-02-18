<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource;

/**
 * The main processor for "update_subresource", "add_subresource" and "delete_subresource" actions.
 */
class ChangeSubresourceProcessor extends SubresourceProcessor
{
    /**
     * {@inheritDoc}
     */
    protected function createContextObject(): ChangeSubresourceContext
    {
        return new ChangeSubresourceContext($this->configProvider, $this->metadataProvider);
    }
}
