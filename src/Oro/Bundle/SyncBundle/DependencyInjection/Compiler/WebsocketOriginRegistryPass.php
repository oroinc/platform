<?php

namespace Oro\Bundle\SyncBundle\DependencyInjection\Compiler;

use Oro\Bundle\SyncBundle\Authentication\Origin\OriginRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Reconfigures the "gos_web_socket.origins.registry" service.
 */
class WebsocketOriginRegistryPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('gos_web_socket.origins.registry')
            ->setClass(OriginRegistry::class)
            ->addArgument(new Reference('oro_sync.authentication.origin_provider'));
    }
}
