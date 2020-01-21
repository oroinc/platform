<?php

namespace Oro\Bundle\SyncBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds "oro_data/" to cache directory path of cache driver used in GosPubSubRouter.
 */
class PubSubRouterCachePass implements CompilerPassInterface
{
    private const CACHE_SERVICE_NAME = 'gos_pubsub_router.php_file.cache';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::CACHE_SERVICE_NAME)) {
            return;
        }

        $definition = $container->getDefinition(self::CACHE_SERVICE_NAME);
        $definition->setArgument(0, '%kernel.cache_dir%/oro_data');
    }
}
