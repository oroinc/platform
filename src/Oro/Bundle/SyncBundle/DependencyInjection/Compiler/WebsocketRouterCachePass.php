<?php

namespace Oro\Bundle\SyncBundle\DependencyInjection\Compiler;

use Oro\Bundle\SyncBundle\Cache\PubSubRouterCache;
use Oro\Bundle\SyncBundle\Cache\PubSubRouterCacheDecorator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Reconfigures the "gos_pubsub_router.php_file.cache" service.
 */
class WebsocketRouterCachePass implements CompilerPassInterface
{
    private const CACHE_SERVICE_ID = 'gos_pubsub_router.php_file.cache';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        // check that the "gos_pubsub_router.php_file.cache" service exists
        $container->getDefinition(self::CACHE_SERVICE_ID);

        $container->removeDefinition(self::CACHE_SERVICE_ID);
        $cacheProvider = new Definition(PubSubRouterCache::class, ['%kernel.cache_dir%/oro', 'gos_pubsub_router']);
        $container->register(self::CACHE_SERVICE_ID, PubSubRouterCacheDecorator::class)
            ->setArguments([$cacheProvider, '%kernel.debug%'])
            ->setPublic(false);
    }
}
