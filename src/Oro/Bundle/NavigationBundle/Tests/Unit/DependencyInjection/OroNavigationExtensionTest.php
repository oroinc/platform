<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\NavigationBundle\DependencyInjection\OroNavigationExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroNavigationExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroNavigationExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        'max_items' => ['value' => 20, 'scope' => 'app'],
                        'title_suffix' => ['value' => '', 'scope' => 'app'],
                        'title_delimiter' => ['value' => '-', 'scope' => 'app'],
                        'breadcrumb_menu' => ['value' => 'application_menu', 'scope' => 'app'],
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_navigation')
        );

        self::assertSame('', $container->getParameter('oro_navigation.js_routing_filename_prefix'));
    }

    /**
     * @dataProvider jsRoutingFilenamePrefixDataProvider
     */
    public function testLoadWithCustomJsRoutingFilenamePrefix(string $prefix, string $expectedPrefix): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $configs = [
            ['js_routing_filename_prefix' => $prefix]
        ];

        $extension = new OroNavigationExtension();
        $extension->load($configs, $container);

        self::assertEquals(
            $expectedPrefix,
            $container->getParameter('oro_navigation.js_routing_filename_prefix')
        );
    }

    public function jsRoutingFilenamePrefixDataProvider(): array
    {
        return [
            ['', ''],
            ['test_prefix', 'test_prefix_'],
            ['test_prefix_', 'test_prefix_'],
            ['__test_prefix__', 'test_prefix_'],
        ];
    }
}
