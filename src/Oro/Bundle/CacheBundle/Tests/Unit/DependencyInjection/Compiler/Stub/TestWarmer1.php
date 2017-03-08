<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\DependencyInjection\Compiler\Stub;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\CacheBundle\Provider\ConfigCacheWarmerInterface;

class TestWarmer1 implements ConfigCacheWarmerInterface
{
    /**
     * {@inheritdoc}
     */
    public function warmUpResourceCache(ContainerBuilder $containerBuilder)
    {
    }
}
