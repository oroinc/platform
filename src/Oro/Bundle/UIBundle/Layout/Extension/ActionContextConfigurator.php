<?php

namespace Oro\Bundle\UIBundle\Layout\Extension;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextConfiguratorInterface;

class ActionContextConfigurator implements ContextConfiguratorInterface
{
    const PARAM_ACTION = 'action';

    /**
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context)
    {
        $context->getDataResolver()
            ->setDefaults([self::PARAM_ACTION => ''])
            ->setAllowedTypes([self::PARAM_ACTION => 'string']);
    }
}
