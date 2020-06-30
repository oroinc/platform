<?php

namespace Oro\Bundle\SyncBundle\DependencyInjection\Compiler;

use Oro\Bundle\SyncBundle\Cache\PubSubRouterCache;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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
        $container->register(self::CACHE_SERVICE_ID, PubSubRouterCache::class)
            ->setArguments(['%kernel.cache_dir%/oro', 'gos_pubsub_router', '%kernel.debug%'])
            ->setPublic(false);
    }
}
