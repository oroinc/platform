<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\NavigationBundle\DependencyInjection\Compiler\MenuExtensionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class MenuExtensionPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject */
    protected $container;

    /** @var MenuExtensionPass */
    protected $menuExtensionPass;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->container = $this->createMock(ContainerBuilder::class);

        $this->menuExtensionPass = new MenuExtensionPass();
    }

    public function testProcessWithoutFactoryService()
    {
        $this->container
            ->expects($this->once())
            ->method('hasDefinition')
            ->with($this->equalTo(MenuExtensionPass::MENU_FACTORY_TAG))
            ->will($this->returnValue(false));

        $this->container
            ->expects($this->never())
            ->method('findTaggedServiceIds');

        $this->container
            ->expects($this->never())
            ->method('getDefinition');

        $this->menuExtensionPass->process($this->container);
    }

    public function testProcessWithoutTaggedServices()
    {
        $this->container
            ->expects($this->once())
            ->method('hasDefinition')
            ->with($this->equalTo(MenuExtensionPass::MENU_FACTORY_TAG))
            ->will($this->returnValue(true));

        $this->container
            ->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with($this->equalTo(MenuExtensionPass::MENU_EXTENSION_TAG))
            ->will($this->returnValue([]));

        $this->container
            ->expects($this->never())
            ->method('getDefinition');

        $this->menuExtensionPass->process($this->container);
    }

    public function testProcess()
    {
        $this->container
            ->expects($this->once())
            ->method('hasDefinition')
            ->with($this->equalTo(MenuExtensionPass::MENU_FACTORY_TAG))
            ->will($this->returnValue(true));

        $this->container
            ->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with($this->equalTo(MenuExtensionPass::MENU_EXTENSION_TAG))
            ->will($this->returnValue([
                'extension_1' => [['priority' => '10']],
                'extension_2' => [['priority' => '20']]
            ]));

        /** @var Definition|\PHPUnit\Framework\MockObject\MockObject $definition */
        $definition = $this->createMock(Definition::class);
        $definition->expects($this->exactly(2))
            ->method('addMethodCall')
            ->withConsecutive(
                ['addExtension', [new Reference('extension_1'), '10']],
                ['addExtension', [new Reference('extension_2'), '20']]
            );

        $this->container
            ->expects($this->once())
            ->method('getDefinition')
            ->with($this->equalTo(MenuExtensionPass::MENU_FACTORY_TAG))
            ->will($this->returnValue($definition));

        $this->menuExtensionPass->process($this->container);
    }
}
