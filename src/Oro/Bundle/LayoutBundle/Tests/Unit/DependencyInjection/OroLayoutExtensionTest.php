<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\LayoutBundle\DependencyInjection\OroLayoutExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroLayoutExtensionTest extends \PHPUnit\Framework\TestCase
{
    private ContainerBuilder $container;
    private OroLayoutExtension $extension;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.environment', 'prod');
        $this->container->setParameter('kernel.debug', false);
        $this->extension = new OroLayoutExtension();
    }

    public function testLoadDefaultConfig(): void
    {
        $this->extension->load([], $this->container);

        self::assertEquals(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        'development_settings_feature_enabled' => ['value' => '%kernel.debug%', 'scope' => 'app'],
                        'debug_block_info' => ['value' => false, 'scope' => 'app'],
                        'debug_developer_toolbar' => ['value' => true, 'scope' => 'app']
                    ]
                ]
            ],
            $this->container->getExtensionConfig('oro_layout')
        );

        // default renderer name
        self::assertTrue(
            $this->container->hasParameter('oro_layout.templating.default'),
            'Failed asserting that default templating parameter is registered'
        );
        self::assertEquals(
            'twig',
            $this->container->getParameter('oro_layout.templating.default')
        );
        // twig renderer
        self::assertTrue(
            $this->container->hasParameter('oro_layout.twig.resources'),
            'Failed asserting that TWIG resources parameter is registered'
        );
        self::assertEquals(
            ['@OroLayout/Layout/div_layout.html.twig'],
            $this->container->getParameter('oro_layout.twig.resources')
        );
        self::assertTrue(
            $this->container->has('oro_layout.twig.extension.layout'),
            'Failed asserting that TWIG extension service is registered'
        );
        // layout theme
        self::assertNull($this->container->getParameter('oro_layout.default_active_theme'));
        self::assertEquals(
            [
                '#Resources/views/layouts/[a-zA-Z][a-zA-Z0-9_\-:]*/theme\.yml$#',
                '#Resources/views/layouts/[a-zA-Z][a-zA-Z0-9_\-:]*/config/[^/]+\.yml$#',
                '#templates/layouts/[a-zA-Z][a-zA-Z0-9_\-:]*/theme\.yml$#',
                '#templates/layouts/[a-zA-Z][a-zA-Z0-9_\-:]*/config/[^/]+\.yml$#'
            ],
            $this->container->getDefinition('oro_layout.theme_extension.resource_provider.theme')->getArgument(5)
        );
        self::assertEquals(
            '[a-zA-Z][a-zA-Z0-9_\-:]*',
            $this->container->getDefinition('oro_layout.theme_extension.configuration.provider')->getArgument(3)
        );
        // debug option
        self::assertEquals('%kernel.debug%', $this->container->getParameter('oro_layout.debug'));
    }

    public function testLoadWithTemplatingAppConfig(): void
    {
        $configs = [
            [
                'templating' => [
                    'default' => 'twig',
                    'twig' => [
                        'resources' => ['@My/Layout/blocks.html.twig']
                    ]
                ]
            ]
        ];

        $this->extension->load($configs, $this->container);

        // default renderer name
        self::assertTrue(
            $this->container->hasParameter('oro_layout.templating.default'),
            'Failed asserting that default templating parameter is registered'
        );
        $this->assertEquals(
            'twig',
            $this->container->getParameter('oro_layout.templating.default')
        );
        // twig renderer
        self::assertTrue(
            $this->container->hasParameter('oro_layout.twig.resources'),
            'Failed asserting that TWIG resources parameter is registered'
        );
        self::assertEquals(
            ['@OroLayout/Layout/div_layout.html.twig', '@My/Layout/blocks.html.twig'],
            $this->container->getParameter('oro_layout.twig.resources')
        );
        self::assertTrue(
            $this->container->has('oro_layout.twig.extension.layout'),
            'Failed asserting that TWIG extension service is registered'
        );
    }

    public function testLoadWithActiveTheme(): void
    {
        $configs = [
            [
                'active_theme' => 'test'
            ]
        ];

        $this->extension->load($configs, $this->container);

        self::assertEquals('test', $this->container->getParameter('oro_layout.default_active_theme'));
    }

    public function testLoadWithDebugOption(): void
    {
        $configs = [
            [
                'debug' => true
            ]
        ];

        $this->extension->load($configs, $this->container);

        self::assertTrue($this->container->getParameter('oro_layout.debug'));
    }
}
