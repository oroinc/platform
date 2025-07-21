<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\NavigationBundle\DependencyInjection\Compiler\MenuExtensionPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class MenuExtensionPassTest extends TestCase
{
    private ContainerBuilder $container;
    private Definition $menuFactory;
    private MenuExtensionPass $compiler;

    #[\Override]
    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->menuFactory = $this->container->register('oro_menu.factory');

        $this->compiler = new MenuExtensionPass();
    }

    public function testProcessWithoutTaggedServices(): void
    {
        $this->compiler->process($this->container);
        $this->assertEquals([], $this->menuFactory->getMethodCalls());
    }

    public function testProcess(): void
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
