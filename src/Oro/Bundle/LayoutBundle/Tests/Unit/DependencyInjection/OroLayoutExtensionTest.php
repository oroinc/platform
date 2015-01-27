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

        $this->assertTrue(
            $container->has('oro_layout.twig.extension.layout'),
            'Failed asserting that TWIG extension service is registered'
        );

        $twigResources = $container->getParameter('oro_layout.twig.resources');
        $this->assertEquals(
            [
                Configuration::DEFAULT_LAYOUT_TWIG_RESOURCE
            ],
            $twigResources
        );
    }

    public function testLoadWithAppConfig()
    {
        $container = new ContainerBuilder();

        $extensionConfig = [
            [
                'twig' => [
                    'resources' => ['MyBundle:Layout:blocks.html.twig']
                ]
            ]
        ];

        $extension = new OroLayoutExtension();
        $extension->load($extensionConfig, $container);

        $this->assertTrue(
            $container->has('oro_layout.twig.extension.layout'),
            'Failed asserting that TWIG extension is registered'
        );

        $twigResources = $container->getParameter('oro_layout.twig.resources');
        $this->assertEquals(
            [
                Configuration::DEFAULT_LAYOUT_TWIG_RESOURCE,
                'MyBundle:Layout:blocks.html.twig'
            ],
            $twigResources
        );
    }

    public function testLoadWithDisabledTwigRenderer()
    {
        $container = new ContainerBuilder();

        $extensionConfig = [
            [
                'twig' => false
            ]
        ];

        $extension = new OroLayoutExtension();
        $extension->load($extensionConfig, $container);

        $this->assertFalse(
            $container->has('oro_layout.twig.extension.layout'),
            'Failed asserting that TWIG extension service is not registered'
        );
        $this->assertFalse(
            $container->hasParameter('oro_layout.twig.resources'),
            'Failed asserting that TWIG resources parameter is not added'
        );
    }
}
