<?php

namespace Oro\Bundle\NotificationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;

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
