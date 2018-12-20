<?php

namespace Oro\Bundle\SyncBundle\DependencyInjection\Compiler;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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

        $configLoader = new CumulativeConfigLoader(
            'oro_sync_websocket_resources',
            new YamlCumulativeFileLoader(self::WEBSOCKET_ROUTING_CONFIG_PATH)
        );

        $routeLoaderDefinition = $container->getDefinition(self::ROUTE_LOADER_SERVICE_NAME);

        foreach ($configLoader->load() as $resourceInfo) {
            $routeLoaderDefinition->addMethodCall('addResource', [$resourceInfo->path]);
        }
    }
}
