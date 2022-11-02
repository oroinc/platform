<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\EntityBundle\Controller\Api\Rest as Api;
use Oro\Bundle\EntityBundle\DependencyInjection\OroEntityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroEntityExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoadDefaultConfigs(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $extension = new OroEntityExtension();
        $extension->load([], $container);

        self::assertNull(
            $container->getParameter('oro_entity.default_query_cache_lifetime')
        );
        self::assertTrue($container->hasDefinition(Api\DictionaryController::class));
        self::assertTrue($container->hasDefinition(Api\EntityAliasController::class));
        self::assertTrue($container->hasDefinition(Api\EntityController::class));
        self::assertTrue($container->hasDefinition(Api\EntityDataController::class));
        self::assertTrue($container->hasDefinition(Api\EntityFieldController::class));
    }

    public function testLoad(): void
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
