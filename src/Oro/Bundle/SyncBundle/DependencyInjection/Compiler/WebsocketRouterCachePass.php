<?php

namespace Oro\Bundle\SyncBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Reconfigures the "gos_pubsub_router.router.websocket" service to store cache inside oro caches directory.
 */
class WebsocketRouterCachePass implements CompilerPassInterface
{
    private const ROUTER_SERVICE_ID = 'gos_pubsub_router.router.websocket';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $routerDef = $container->getDefinition(self::ROUTER_SERVICE_ID);
        $routerDef->addMethodCall('setOption', ['cache_dir', '%kernel.cache_dir%/oro/gos_pubsub_router']);
    }
}
