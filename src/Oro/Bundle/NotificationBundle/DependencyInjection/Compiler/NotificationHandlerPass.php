<?php

namespace Oro\Bundle\NotificationBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class NotificationHandlerPass implements CompilerPassInterface
{
    use TaggedServicesCompilerPassTrait;

    const TAG         = 'notification.handler';
    const SERVICE_KEY = 'oro_notification.manager';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerTaggedServices($container, self::SERVICE_KEY, self::TAG, 'addHandler');
    }
}
