<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Component\Config\CumulativeResourceManager;
use Oro\Bundle\LayoutBundle\DependencyInjection\Configuration;
use Oro\Bundle\LayoutBundle\DependencyInjection\OroLayoutExtension;

use Oro\Bundle\LayoutBundle\Tests\Unit\Stubs\Bundles\TestBundle\TestBundle;

class OroLayoutExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadDefaultConfig()
    {
        $container = new ContainerBuilder();

        $extensionConfig = [];

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

        $this->assertTrue(
            $container->has(OroLayoutExtension::THEME_MANAGER_SERVICE_ID),
            'Failed asserting that theme manager is registered'
        );
    }

    public function testLoadWithTemplatingAppConfig()
    {
        $container = new ContainerBuilder();

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

        $extensionConfig = [
            [
                'themes'       => [
                    'gold' => [
                        'label' => 'Gold theme'
                    ]
                ],
                'active_theme' => 'gold'
            ]
        ];

        $extension = new OroLayoutExtension();
        $extension->load($extensionConfig, $container);

        $manager = $container->get(OroLayoutExtension::THEME_MANAGER_SERVICE_ID);
        $result  = $manager->getTheme('gold');

        $this->assertFalse($result->isHidden());
        $this->assertSame(Configuration::BASE_THEME_IDENTIFIER, $result->getParentTheme());
        $this->assertSame('Gold theme', $result->getLabel());
        $this->assertSame('gold', $result->getDirectory());
    }

    public function testLoadWithBundleThemesConfig()
    {
        $container = new ContainerBuilder();

        $bundle = new TestBundle();
        CumulativeResourceManager::getInstance()->clear()
            ->setBundles([$bundle->getName() => get_class($bundle)]);

        $extension = new OroLayoutExtension();
        $extension->load([], $container);

        $expectedResult = ['base', 'oro-black'];
        $result         = $container->get(OroLayoutExtension::THEME_MANAGER_SERVICE_ID)->getThemeNames();
        $this->assertSame(sort($expectedResult), sort($result));
    }
}
