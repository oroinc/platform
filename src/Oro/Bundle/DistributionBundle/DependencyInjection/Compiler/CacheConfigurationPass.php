<?php

namespace Oro\Bundle\DistributionBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Makes sure that all abstract caches are defined.
 */
class CacheConfigurationPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('oro.cache.abstract')
            && !$container->hasDefinition('oro.cache.abstract.without_memory_cache')
        ) {
            $container->setDefinition(
                'oro.cache.abstract.without_memory_cache',
                clone $container->getDefinition('oro.cache.abstract')
            );
        }
    }
}
