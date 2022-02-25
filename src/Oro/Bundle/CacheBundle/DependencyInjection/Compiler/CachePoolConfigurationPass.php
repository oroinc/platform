<?php

namespace Oro\Bundle\CacheBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Removes tags from cache services.
 * This is necessary in order to be able to describe caches with unique namespaces.
 */
class CachePoolConfigurationPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(CacheConfigurationPass::DATA_CACHE_POOL)) {
            $this->removeCachePoolTag($container, CacheConfigurationPass::DATA_CACHE_POOL);
        }

        if ($container->hasDefinition(CacheConfigurationPass::DATA_CACHE_POOL_WITHOUT_MEMORY_CACHE)) {
            $this->removeCachePoolTag($container, CacheConfigurationPass::DATA_CACHE_POOL_WITHOUT_MEMORY_CACHE);
        }
    }

    private function removeCachePoolTag(ContainerBuilder $container, string $serviceId): void
    {
        /** @var ChildDefinition $definition */
        $definition = $container->getDefinition($serviceId);
        $definition->clearTag('cache.pool');
        $container->setDefinition($serviceId, $definition);
    }
}
