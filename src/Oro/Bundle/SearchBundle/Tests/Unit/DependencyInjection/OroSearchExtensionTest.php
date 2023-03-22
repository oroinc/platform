<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\SearchBundle\DependencyInjection\OroSearchExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroSearchExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroSearchExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());

        self::assertEquals(
            'orm:',
            $container->getParameter('oro_search.engine_dsn')
        );
        self::assertSame(
            [],
            $container->getParameter('oro_search.engine_parameters')
        );
        self::assertFalse(
            $container->getParameter('oro_search.log_queries')
        );
        self::assertEquals(
            '@OroSearch/Datagrid/itemContainer.html.twig',
            $container->getParameter('oro_search.twig.item_container_template')
        );
    }

    public function testLoadWithCustomConfigs(): void
    {
        $container = new ContainerBuilder();

        $config = [
            'engine_dsn'        => 'some-other-engine:',
            'engine_parameters' => ['some-engine-parameters'],
            'log_queries'       => true
        ];

        $extension = new OroSearchExtension();
        $extension->load(['oro_search' => $config], $container);

        self::assertEquals(
            $config['engine_dsn'],
            $container->getParameter('oro_search.engine_dsn')
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
            '@OroSearch/Datagrid/itemContainer.html.twig',
            $container->getParameter('oro_search.twig.item_container_template')
        );
    }
}
