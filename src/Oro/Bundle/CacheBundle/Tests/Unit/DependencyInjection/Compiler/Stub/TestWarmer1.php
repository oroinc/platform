<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\DependencyInjection\Compiler\Stub;

use Oro\Bundle\CacheBundle\Provider\ConfigCacheWarmerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TestWarmer1 implements ConfigCacheWarmerInterface
{
    /**
     * {@inheritdoc}
     */
    public function warmUpResourceCache(ContainerBuilder $containerBuilder)
    {
    }
}
