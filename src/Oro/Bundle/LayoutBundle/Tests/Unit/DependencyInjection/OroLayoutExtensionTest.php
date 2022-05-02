<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\LayoutBundle\DependencyInjection\OroLayoutExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroLayoutExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoadDefaultConfig(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');
        $container->setParameter('kernel.debug', false);

        $extensionConfig = [];

        $extension = new OroLayoutExtension();
        $extension->load($extensionConfig, $container);

        self::assertEquals(
            [
                [
                    'settings' => [
                        'resolved'                             => true,
                        'development_settings_feature_enabled' => ['value' => '%kernel.debug%', 'scope' => 'app'],
                        'debug_block_info'                     => ['value' => false, 'scope' => 'app'],
                        'debug_developer_toolbar'              => ['value' => true, 'scope' => 'app']
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_layout')
        );

        // default renderer name
        self::assertTrue(
            $container->hasParameter('oro_layout.templating.default'),
            'Failed asserting that default templating parameter is registered'
        );
        self::assertEquals(
            'twig',
            $container->getParameter('oro_layout.templating.default')
        );
        // twig renderer
        self::assertTrue(
            $container->hasParameter('oro_layout.twig.resources'),
            'Failed asserting that TWIG resources parameter is registered'
        );
        self::assertEquals(
            ['@OroLayout/Layout/div_layout.html.twig'],
            $container->getParameter('oro_layout.twig.resources')
        );
        self::assertTrue(
            $container->has('oro_layout.twig.extension.layout'),
            'Failed asserting that TWIG extension service is registered'
        );
        // layout theme
        self::assertNull($container->getParameter('oro_layout.default_active_theme'));
        self::assertEquals(
            [
                '#Resources/views/layouts/[a-zA-Z][a-zA-Z0-9_\-:]*/theme\.yml$#',
                '#Resources/views/layouts/[a-zA-Z][a-zA-Z0-9_\-:]*/config/[^/]+\.yml$#'
            ],
            $container->getDefinition('oro_layout.theme_extension.resource_provider.theme')->getArgument(5)
        );
        self::assertEquals(
            '[a-zA-Z][a-zA-Z0-9_\-:]*',
            $container->getDefinition('oro_layout.theme_extension.configuration.provider')->getArgument(3)
        );
        // debug option
        self::assertEquals('%kernel.debug%', $container->getParameter('oro_layout.debug'));
    }

    public function testLoadWithTemplatingAppConfig(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');
        $container->setParameter('kernel.debug', false);

        $extensionConfig = [
            [
                'templating' => [
                    'default' => 'twig',
                    'twig'    => [
                        'resources' => ['@My/Layout/blocks.html.twig']
                    ]
                ]
            ]
        ];

        $extension = new OroLayoutExtension();
        $extension->load($extensionConfig, $container);

        // default renderer name
        self::assertTrue(
            $container->hasParameter('oro_layout.templating.default'),
            'Failed asserting that default templating parameter is registered'
        );
        $this->assertEquals(
            'twig',
            $container->getParameter('oro_layout.templating.default')
        );
        // twig renderer
        self::assertTrue(
            $container->hasParameter('oro_layout.twig.resources'),
            'Failed asserting that TWIG resources parameter is registered'
        );
        self::assertEquals(
            ['@OroLayout/Layout/div_layout.html.twig', '@My/Layout/blocks.html.twig'],
            $container->getParameter('oro_layout.twig.resources')
        );
        self::assertTrue(
            $container->has('oro_layout.twig.extension.layout'),
            'Failed asserting that TWIG extension service is registered'
        );
    }

    public function testLoadWithActiveTheme(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');
        $container->setParameter('kernel.debug', false);

        $extensionConfig = [
            [
                'active_theme' => 'test'
            ]
        ];

        $extension = new OroLayoutExtension();
        $extension->load($extensionConfig, $container);

        self::assertEquals('test', $container->getParameter('oro_layout.default_active_theme'));
    }

    public function testLoadWithDebugOption(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');
        $container->setParameter('kernel.debug', false);

        $extensionConfig = [
            [
                'debug' => true
            ]
        ];

        $extension = new OroLayoutExtension();
        $extension->load($extensionConfig, $container);

        self::assertTrue($container->getParameter('oro_layout.debug'));
    }
}
