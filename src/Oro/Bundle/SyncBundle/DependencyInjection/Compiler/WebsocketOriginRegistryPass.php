<?php

namespace Oro\Bundle\SyncBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Reconfigures the "gos_web_socket.registry.origins" service.
 */
class WebsocketOriginRegistryPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('gos_web_socket.registry.origins')
            ->setFactory(new Reference('oro_sync.authentication.origin_registry.factory'));
    }
}
