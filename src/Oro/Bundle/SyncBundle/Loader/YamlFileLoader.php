<?php

namespace Oro\Bundle\SyncBundle\Loader;

use Gos\Bundle\PubSubRouterBundle\Loader\YamlFileLoader as BaseLoader;
use Gos\Bundle\PubSubRouterBundle\Router\RouteCollection;

/**
 * BC layer for loading websocket_routing.yml configurations to Gos PubSub.
 */
class YamlFileLoader extends BaseLoader
{
    public const TYPE = 'oro_websocket_routing_yaml';

    /**
     * {@inheritDoc}
     */
    protected function parseRoute(RouteCollection $collection, string $name, array $config, string $path): void
    {
        if (is_array($config['handler']) && array_key_exists('callback', $config['handler'])) {
            $config['handler'] = $config['handler']['callback'];
        }

        if (!empty($config['requirements']) && is_array($config['requirements'])) {
            foreach ($config['requirements'] as $key => $requirement) {
                if (!is_string($requirement) && isset($requirement['pattern'])) {
                    $pattern = $requirement['pattern'];
                    if (!empty($requirement['wildcard'])) {
                        $pattern .= '|\*';
                    }

                    $config['requirements'][$key] = $pattern;
                }
            }
        }

        parent::parseRoute($collection, $name, $config, $path);
    }

    /**
     * {@inheritDoc}
     */
    protected function doSupports($resource, string $type = null): bool
    {
        return \is_string($resource)
            && \in_array(pathinfo($resource, PATHINFO_EXTENSION), ['yml', 'yaml'], true)
            && (self::TYPE === $type);
    }
}
