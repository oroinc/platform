<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

/**
 * Configures the layout context with the current action identifier.
 *
 * This configurator registers the `action` context variable, which contains the
 * name of the current action being executed. This allows layout updates and blocks
 * to conditionally render content based on the current action context.
 */
class ActionContextConfigurator implements ContextConfiguratorInterface
{
    #[\Override]
    public function configureContext(ContextInterface $context)
    {
        $context->getResolver()
            ->setDefaults(['action' => ''])
            ->setAllowedTypes('action', 'string');
    }
}
