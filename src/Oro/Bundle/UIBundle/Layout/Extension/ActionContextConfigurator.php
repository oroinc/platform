<?php

namespace Oro\Bundle\UIBundle\Layout\Extension;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextConfiguratorInterface;

class ActionContextConfigurator implements ContextConfiguratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context)
    {
        $context->getDataResolver()
            ->setDefaults(['action' => ''])
            ->setAllowedTypes(['action' => 'string']);
    }
}
