<?php

namespace Oro\Bundle\RequireJSBundle\DependencyInjection\Compiler;

use Doctrine\Common\Cache\PhpFileCache;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * This compiler pass replaces the service definition of the cache provider
 * for the RequireJS configuration with the Doctrine PhpFileCache if the
 * OroCacheBundle is not available.
 *
 * @author Stefano Arlandini <sarlandini@alice.it>
 */
class CacheProviderPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('oro.cache.abstract')) {
            $definition = new Definition(PhpFileCache::class, ['%kernel.cache_dir%/oro_data']);
            $definition->setDecoratedService('oro_requirejs.cache');

            $container->setDefinition('oro_requirejs.decorating_cache', $definition);
        }
    }
}
