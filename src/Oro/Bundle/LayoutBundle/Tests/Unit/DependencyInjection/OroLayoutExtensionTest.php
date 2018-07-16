<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\LayoutBundle\DependencyInjection\Configuration;
use Oro\Bundle\LayoutBundle\DependencyInjection\OroLayoutExtension;
use Oro\Bundle\LayoutBundle\EventListener\LayoutListener;
use Oro\Bundle\LayoutBundle\EventListener\ThemeListener;
use Oro\Bundle\LayoutBundle\Request\LayoutHelper;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroLayoutExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoadDefaultConfig()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        $extensionConfig = [];

        $extension = new OroLayoutExtension();
        $extension->load($extensionConfig, $container);

        // view annotations
        $this->assertEquals(
            [
                LayoutListener::class,
                ThemeListener::class,
                LayoutHelper::class
            ],
            $extension->getClassesToCompile(),
            'Failed asserting that @Layout annotation is enabled'
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
            [Configuration::DEFAULT_LAYOUT_PHP_RESOURCE],
            $container->getParameter('oro_layout.php.resources')
        );
        // twig renderer
        $this->assertTrue(
            $container->hasParameter('oro_layout.twig.resources'),
            'Failed asserting that TWIG resources parameter is registered'
        );
        $this->assertEquals(
            [Configuration::DEFAULT_LAYOUT_TWIG_RESOURCE],
            $container->getParameter('oro_layout.twig.resources')
        );
        $this->assertTrue(
            $container->has('oro_layout.twig.extension.layout'),
            'Failed asserting that TWIG extension service is registered'
        );
        // theme services
        $this->assertTrue(
            $container->has(OroLayoutExtension::THEME_MANAGER_SERVICE_ID),
            'Failed asserting that theme manager is registered'
        );
    }

    public function testLoadWithTemplatingAppConfig()
    {
        $container = new ContainerBuilder();
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
            [Configuration::DEFAULT_LAYOUT_PHP_RESOURCE, 'MyBundle:Layout/php'],
            $container->getParameter('oro_layout.php.resources')
        );
        // twig renderer
        $this->assertTrue(
            $container->hasParameter('oro_layout.twig.resources'),
            'Failed asserting that TWIG resources parameter is registered'
        );
        $this->assertEquals(
            [Configuration::DEFAULT_LAYOUT_TWIG_RESOURCE, 'MyBundle:Layout:blocks.html.twig'],
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

    public function testLoadWithThemesAppConfig()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        $extensionConfig = [
            [
                'themes'       => [
                    'gold' => [
                        'label'  => 'Gold theme',
                        'icon'   => 'gold.ico',
                        'groups' => ['main', 'another']
                    ]
                ],
                'active_theme' => 'gold'
            ]
        ];

        $extension = new OroLayoutExtension();
        $extension->load($extensionConfig, $container);

        $manager = $container->get(OroLayoutExtension::THEME_MANAGER_SERVICE_ID);
        $result  = $manager->getTheme('gold');

        $this->assertNull($result->getParentTheme());
        $this->assertSame('Gold theme', $result->getLabel());
        $this->assertSame('gold.ico', $result->getIcon());
        $this->assertSame('gold', $result->getDirectory());
        $this->assertEquals(['main', 'another'], $result->getGroups());
    }

    public function testGetAlias()
    {
        $extension = new OroLayoutExtension();
        $this->assertEquals('oro_layout', $extension->getAlias());
    }

    protected function normalizeResources(array &$resources)
    {
        ksort($resources);
        array_walk(
            $resources,
            function (&$resource) {
                ksort($resource);
                array_walk(
                    $resource,
                    function (&$subResource) {
                        sort($subResource);
                    }
                );
            }
        );
    }
}
