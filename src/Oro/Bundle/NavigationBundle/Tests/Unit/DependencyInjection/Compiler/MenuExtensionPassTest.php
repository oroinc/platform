<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\NavigationBundle\DependencyInjection\Compiler\MenuExtensionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class MenuExtensionPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerBuilder */
    private $container;

    /** @var Definition */
    private $menuFactory;

    /** @var MenuExtensionPass */
    private $compiler;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->menuFactory = $this->container->register('oro_menu.factory');

        $this->compiler = new MenuExtensionPass();
    }

    public function testProcessWithoutTaggedServices()
    {
        $this->compiler->process($this->container);
        $this->assertEquals([], $this->menuFactory->getMethodCalls());
    }

    public function testProcess()
    {
        $this->container->setDefinition('extension_1', new Definition())
            ->addTag('oro_navigation.menu_extension');
        $this->container->setDefinition('extension_2', new Definition())
            ->addTag('oro_navigation.menu_extension', ['priority' => 100]);
        $this->container->setDefinition('extension_3', new Definition())
            ->addTag('oro_navigation.menu_extension', ['priority' => -100]);

        $this->compiler->process($this->container);
        $this->assertEquals(
            [
                ['addExtension', [new Reference('extension_1'), 0]],
                ['addExtension', [new Reference('extension_2'), 100]],
                ['addExtension', [new Reference('extension_3'), -100]]
            ],
            $this->menuFactory->getMethodCalls()
        );
    }
}
