<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

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
