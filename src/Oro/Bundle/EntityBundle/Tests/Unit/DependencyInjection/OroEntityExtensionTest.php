<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\EntityBundle\DependencyInjection\OroEntityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroEntityExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoadDefaultConfigs()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $extension = new OroEntityExtension();
        $extension->load([], $container);

        self::assertNull(
            $container->getParameter('oro_entity.default_query_cache_lifetime')
        );
    }

    public function testLoad()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $config = [
            'default_query_cache_lifetime' => 123
        ];

        $extension = new OroEntityExtension();
        $extension->load([$config], $container);

        self::assertSame(
            $config['default_query_cache_lifetime'],
            $container->getParameter('oro_entity.default_query_cache_lifetime')
        );
    }
}
