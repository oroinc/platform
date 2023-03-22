<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\EntityBundle\DependencyInjection\OroEntityExtension;
use Oro\Bundle\EntityBundle\Tests\Unit\Fixtures\Bundles\TestBundle1\TestBundle1;
use Oro\Bundle\EntityBundle\Tests\Unit\Fixtures\Bundles\TestBundle2\TestBundle2;
use Oro\Component\Config\CumulativeResourceManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroEntityExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        CumulativeResourceManager::getInstance()->clear();

        $extension = new OroEntityExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());

        self::assertEquals(
            [],
            $container->getDefinition('oro_entity.entity_name_provider.configurable')->getArgument('$fields')
        );
        self::assertNull($container->getParameter('oro_entity.default_query_cache_lifetime'));
        self::assertEquals([], $container->getParameter('oro_entity.hidden_fields'));
    }

    public function testLoadWithCustomConfigs(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $config = [
            'entity_name_representation' => ['Test\Entity' => ['full' => ['name']]],
            'default_query_cache_lifetime' => 123
        ];

        $bundle1 = new TestBundle1();
        $bundle2 = new TestBundle2();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $bundle1->getName() => get_class($bundle1),
                $bundle2->getName() => get_class($bundle2)
            ]);

        $extension = new OroEntityExtension();
        $extension->load([$config], $container);

        self::assertEquals(
            $config['entity_name_representation'],
            $container->getDefinition('oro_entity.entity_name_provider.configurable')->getArgument('$fields')
        );
        self::assertSame(
            $config['default_query_cache_lifetime'],
            $container->getParameter('oro_entity.default_query_cache_lifetime')
        );
        self::assertEquals(
            [
                'Test\Entity1' => ['field2' => true],
                'Test\Entity2' => ['field1' => true],
                'Test\Entity3' => ['field1' => true]
            ],
            $container->getParameter('oro_entity.hidden_fields')
        );
    }
}
