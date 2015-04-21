<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextConfiguratorInterface;

class ActionContextConfigurator implements ContextConfiguratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context)
    {
        $context->getResolver()
            ->setDefaults(['action' => ''])
            ->setAllowedTypes(['action' => 'string']);
    }
}
