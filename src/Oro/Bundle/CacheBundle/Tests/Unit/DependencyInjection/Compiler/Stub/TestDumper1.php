<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\DependencyInjection\Compiler\Stub;

use Oro\Component\Config\Dumper\ConfigMetadataDumperInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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
