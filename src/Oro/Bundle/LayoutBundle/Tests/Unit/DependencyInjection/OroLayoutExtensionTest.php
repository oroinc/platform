<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\LayoutBundle\DependencyInjection\OroLayoutExtension;
use Oro\Bundle\LayoutBundle\DependencyInjection\Configuration;

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
    }

    public function testLoadWithAppConfig()
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
}
