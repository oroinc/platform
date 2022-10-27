<?php

namespace Oro\Bundle\SyncBundle\DependencyInjection\Compiler;

use Oro\Bundle\SyncBundle\Loader\YamlFileLoader;
use Oro\Component\Config\Loader\ContainerBuilderAdapter;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\NullCumulativeFileLoader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers all "Resources/config/oro/websocket_routing.yml" files as Gos PubSub routing resources.
 */
class WebsocketRouterConfigurationPass implements CompilerPassInterface
{
    private const WEBSOCKET_ROUTING_CONFIG_PATH = 'Resources/config/oro/websocket_routing.yml';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $configLoader = new CumulativeConfigLoader(
            'oro_sync_websocket_resources',
            new NullCumulativeFileLoader(self::WEBSOCKET_ROUTING_CONFIG_PATH)
        );
        $resources = $configLoader->load(new ContainerBuilderAdapter($container));
        $registeredResource = $container->getParameter('gos_web_socket.router_resources');
        foreach ($resources as $resource) {
            $registeredResource[] = [
                'resource' => $resource->path,
                // Load websocket_routing.yml files with Oro\Bundle\SyncBundle\Loader\YamlFileLoader for BC
                'type' => YamlFileLoader::TYPE
            ];
        }

        $container->setParameter('gos_web_socket.router_resources', $registeredResource);
    }
}
