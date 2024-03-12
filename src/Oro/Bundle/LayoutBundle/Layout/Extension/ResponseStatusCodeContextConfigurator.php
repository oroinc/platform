<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

/**
 * Adds "response_status_code" element to layout context.
 */
class ResponseStatusCodeContextConfigurator implements ContextConfiguratorInterface
{
    public function configureContext(ContextInterface $context): void
    {
        $context
            ->getResolver()
            ->define('response_status_code')
            ->default(200)
            ->allowedTypes('int');
    }
}
