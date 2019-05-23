<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\LayoutBundle\DependencyInjection\OroLayoutExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroLayoutExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoadDefaultConfig()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');
        $container->setParameter('kernel.debug', false);

        $extensionConfig = [];

        $extension = new OroLayoutExtension();
        $extension->load($extensionConfig, $container);

        $this->assertEquals(
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
            $container->getExtensionConfig($extension->getAlias())
        );

        // default renderer name
        $this->assertTrue(
            $container->hasParameter('oro_layout.templating.default'),
            'Failed asserting that default templating parameter is registered'
        );
        $this->assertEquals(
            'twig',
            $container->getParameter('oro_layout.templating.default')
        );
        // php renderer
        $this->assertTrue(
            $container->hasParameter('oro_layout.php.resources'),
            'Failed asserting that PHP resources parameter is registered'
        );
        $this->assertEquals(
            ['OroLayoutBundle:Layout/php'],
            $container->getParameter('oro_layout.php.resources')
        );
        // twig renderer
        $this->assertTrue(
            $container->hasParameter('oro_layout.twig.resources'),
            'Failed asserting that TWIG resources parameter is registered'
        );
        $this->assertEquals(
            ['OroLayoutBundle:Layout:div_layout.html.twig'],
            $container->getParameter('oro_layout.twig.resources')
        );
        $this->assertTrue(
            $container->has('oro_layout.twig.extension.layout'),
            'Failed asserting that TWIG extension service is registered'
        );
        // layout theme
        $this->assertNull($container->getParameter('oro_layout.default_active_theme'));
        $this->assertEquals(
            [
                '#Resources/views/layouts/[a-zA-Z][a-zA-Z0-9_\-:]*/theme\.yml$#',
                '#Resources/views/layouts/[a-zA-Z][a-zA-Z0-9_\-:]*/config/[^/]+\.yml$#'
            ],
            $container->getDefinition('oro_layout.theme_extension.resource_provider.theme')->getArgument(5)
        );
        $this->assertEquals(
            '[a-zA-Z][a-zA-Z0-9_\-:]*',
            $container->getDefinition('oro_layout.theme_extension.configuration.provider')->getArgument(3)
        );
        // debug option
        $this->assertEquals('%kernel.debug%', $container->getParameter('oro_layout.debug'));
    }

    public function testLoadWithTemplatingAppConfig()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');
        $container->setParameter('kernel.debug', false);

        $extensionConfig = [
            [
                'templating' => [
                    'default' => 'php',
                    'php'     => [
                        'resources' => ['MyBundle:Layout/php']
                    ],
                    'twig'    => [
                        'resources' => ['MyBundle:Layout:blocks.html.twig']
                    ]
                ]
            ]
        ];

        $extension = new OroLayoutExtension();
        $extension->load($extensionConfig, $container);

        // default renderer name
        $this->assertTrue(
            $container->hasParameter('oro_layout.templating.default'),
            'Failed asserting that default templating parameter is registered'
        );
        $this->assertEquals(
            'php',
            $container->getParameter('oro_layout.templating.default')
        );
        // php renderer
        $this->assertTrue(
            $container->hasParameter('oro_layout.php.resources'),
            'Failed asserting that PHP resources parameter is registered'
        );
        $this->assertEquals(
            ['OroLayoutBundle:Layout/php', 'MyBundle:Layout/php'],
            $container->getParameter('oro_layout.php.resources')
        );
        // twig renderer
        $this->assertTrue(
            $container->hasParameter('oro_layout.twig.resources'),
            'Failed asserting that TWIG resources parameter is registered'
        );
        $this->assertEquals(
            ['OroLayoutBundle:Layout:div_layout.html.twig', 'MyBundle:Layout:blocks.html.twig'],
            $container->getParameter('oro_layout.twig.resources')
        );
        $this->assertTrue(
            $container->has('oro_layout.twig.extension.layout'),
            'Failed asserting that TWIG extension service is registered'
        );
    }

    public function testLoadWithDisabledTwigRenderer()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');
        $container->setParameter('kernel.debug', false);

        $extensionConfig = [
            [
                'templating' => [
                    'php'  => false,
                    'twig' => false
                ]
            ]
        ];

        $extension = new OroLayoutExtension();
        $extension->load($extensionConfig, $container);

        // default renderer name
        $this->assertTrue(
            $container->hasParameter('oro_layout.templating.default'),
            'Failed asserting that default templating parameter is registered'
        );
        $this->assertEquals(
            'twig',
            $container->getParameter('oro_layout.templating.default')
        );
        // php renderer
        $this->assertFalse(
            $container->hasParameter('oro_layout.php.resources'),
            'Failed asserting that PHP resources parameter is not registered'
        );
        // twig renderer
        $this->assertFalse(
            $container->hasParameter('oro_layout.twig.resources'),
            'Failed asserting that TWIG resources parameter is not registered'
        );
        $this->assertFalse(
            $container->has('oro_layout.twig.extension.layout'),
            'Failed asserting that TWIG extension service is not registered'
        );
    }

    public function testLoadWithActiveTheme()
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

        $this->assertEquals('test', $container->getParameter('oro_layout.default_active_theme'));
    }

    public function testLoadWithDebugOption()
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

        $this->assertTrue($container->getParameter('oro_layout.debug'));
    }

    public function testGetAlias()
    {
        $extension = new OroLayoutExtension();
        $this->assertEquals('oro_layout', $extension->getAlias());
    }
}
