<?php

namespace Oro\Bundle\SyncBundle\DependencyInjection\Compiler;

use Oro\Component\Config\Loader\ContainerBuilderAdapter;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\NullCumulativeFileLoader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers all "Resources/config/oro/websocket_routing.yml" files
 * in "gos_pubsub_router.loader.websocket" service.
 */
class WebsocketRouterConfigurationPass implements CompilerPassInterface
{
    private const ROUTE_LOADER_SERVICE_NAME = 'gos_pubsub_router.loader.websocket';
    private const WEBSOCKET_ROUTING_CONFIG_PATH = 'Resources/config/oro/websocket_routing.yml';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::ROUTE_LOADER_SERVICE_NAME)) {
            return;
        }

        $routeLoaderDefinition = $container->getDefinition(self::ROUTE_LOADER_SERVICE_NAME);

        $configLoader = new CumulativeConfigLoader(
            'oro_sync_websocket_resources',
            new NullCumulativeFileLoader(self::WEBSOCKET_ROUTING_CONFIG_PATH)
        );
        $resources = $configLoader->load(new ContainerBuilderAdapter($container));
        foreach ($resources as $resource) {
            $routeLoaderDefinition->addMethodCall('addResource', [$resource->path]);
        }
    }
}
