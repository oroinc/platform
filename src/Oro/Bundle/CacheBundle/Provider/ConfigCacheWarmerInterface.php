<?php

namespace Oro\Bundle\CacheBundle\Provider;

use Symfony\Component\DependencyInjection\ContainerBuilder;

interface ConfigCacheWarmerInterface
{
    /**
     * @param ContainerBuilder $containerBuilder
     */
    public function warmUpResourceCache(ContainerBuilder $containerBuilder);
}
