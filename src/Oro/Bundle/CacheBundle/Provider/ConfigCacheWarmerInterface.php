<?php

namespace Oro\Bundle\CacheBundle\Provider;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Interface for classes that support warming their cache and save in the container the loaded resources
 */
interface ConfigCacheWarmerInterface
{
    /**
     * This method should warms up the cache and registered in the container the loaded resources.
     * Based on these resources will generated a cache metadata.
     * Later these information will be used to check that resources have been modified
     * since the last warm-up of the cache
     *
     * @param ContainerBuilder $containerBuilder
     */
    public function warmUpResourceCache(ContainerBuilder $containerBuilder);
}
