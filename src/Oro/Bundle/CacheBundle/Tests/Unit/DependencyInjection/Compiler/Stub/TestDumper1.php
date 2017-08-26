<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\DependencyInjection\Compiler\Stub;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Component\Config\Dumper\ConfigMetadataDumperInterface;

class TestDumper1 implements ConfigMetadataDumperInterface
{
    /**
     * {@inheritdoc}
     */
    public function dump(ContainerBuilder $container)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh()
    {
        return true;
    }
}
