<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\SearchBundle\DependencyInjection\OroSearchExtension;
use Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Bundle\FirstEngineBundle\FirstEngineBundle;
use Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Bundle\SecondEngineBundle\SecondEngineBundle;
use Oro\Component\Config\CumulativeResourceManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroSearchExtensionTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $bundle1 = new FirstEngineBundle();
        $bundle2 = new SecondEngineBundle();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $bundle1->getName() => get_class($bundle1),
                $bundle2->getName() => get_class($bundle2)
            ]);
    }

    public function testLoadDefaultConfig()
    {
        $container = new ContainerBuilder();

        $extension = new OroSearchExtension();
        $extension->load([], $container);

        self::assertEquals(
            'orm',
            $container->getParameter('oro_search.engine')
        );
        self::assertSame(
            [],
            $container->getParameter('oro_search.engine_parameters')
        );
        self::assertFalse(
            $container->getParameter('oro_search.log_queries')
        );
        self::assertEquals(
            'OroSearchBundle:Datagrid:itemContainer.html.twig',
            $container->getParameter('oro_search.twig.item_container_template')
        );
    }

    public function testLoad()
    {
        $container = new ContainerBuilder();

        $config = [
            'engine'            => 'some-other-engine',
            'engine_parameters' => ['some-engine-parameters'],
            'log_queries'       => true
        ];

        $extension = new OroSearchExtension();
        $extension->load(['oro_search' => $config], $container);

        self::assertEquals(
            $config['engine'],
            $container->getParameter('oro_search.engine')
        );
        self::assertEquals(
            $config['engine_parameters'],
            $container->getParameter('oro_search.engine_parameters')
        );
        self::assertEquals(
            $config['log_queries'],
            $container->getParameter('oro_search.log_queries')
        );
        self::assertEquals(
            'OroSearchBundle:Datagrid:itemContainer.html.twig',
            $container->getParameter('oro_search.twig.item_container_template')
        );
    }

    public function testOrmSearchEngineLoad()
    {
        $container = new ContainerBuilder();

        $config = [
            'engine' => 'orm'
        ];

        $extension = new OroSearchExtension();
        $extension->load(['oro_search' => $config], $container);

        self::assertTrue($container->hasDefinition('test_orm_service'));
    }

    public function testOtherSearchEngineLoad()
    {
        $container = new ContainerBuilder();

        $config = [
            'engine' => 'other_engine'
        ];

        $extension = new OroSearchExtension();
        $extension->load(['oro_search' => $config], $container);

        self::assertTrue($container->hasDefinition('test_engine_service'));
        self::assertTrue($container->hasDefinition('test_engine_first_bundle_service'));
        self::assertTrue($container->hasDefinition('test_engine_second_bundle_service'));
    }
}
