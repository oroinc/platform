<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\NavigationBundle\DependencyInjection\Compiler\MenuBuilderChainPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class MenuBuilderPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcessSkip()
    {
        $menuHelperDefinition = $this->createMock(Definition::class);

        $containerMock = $this->createMock(ContainerBuilder::class);
        $containerMock->expects($this->exactly(2))
            ->method('hasDefinition')
            ->with(
                $this->logicalOr(
                    $this->equalTo('oro_menu.builder_chain'),
                    $this->equalTo('oro_navigation.item.factory')
                )
            )
            ->willReturn(false);
        $containerMock->expects($this->once())
            ->method('getDefinition')
            ->with('knp_menu.helper')
            ->willReturn($menuHelperDefinition);
        $containerMock->expects($this->never())
            ->method('findTaggedServiceIds');

        $menuHelperDefinition->expects($this->once())
            ->method('setPublic')
            ->with(true);

        $compilerPass = new MenuBuilderChainPass();
        $compilerPass->process($containerMock);
    }

    public function testProcess()
    {
        $menuHelperDefinition = $this->createMock(Definition::class);
        $definition = $this->createMock(Definition::class);
        $definition->expects($this->exactly(4))
            ->method('addMethodCall');
        $definition->expects($this->at(0))
            ->method('addMethodCall')
            ->with('addBuilder', [new Reference('service1'), 'test']);
        $definition->expects($this->at(1))
            ->method('addMethodCall')
            ->with('addBuilder', [new Reference('service2'), 'test']);
        $definition->expects($this->at(2))
            ->method('addMethodCall')
            ->with('addBuilder', [new Reference('service1')]);
        $definition->expects($this->at(3))
            ->method('addMethodCall')
            ->with('addBuilder', [new Reference('service2')]);

        $serviceIds = [
            'service1' => [[]],
            'service2' => [[]],
        ];

        $containerMock = $this->createMock(ContainerBuilder::class);
        $containerMock->expects($this->exactly(2))
            ->method('hasDefinition')
            ->with(
                $this->logicalOr(
                    $this->equalTo('oro_menu.builder_chain'),
                    $this->equalTo('oro_navigation.item.factory')
                )
            )
            ->willReturn(true);

        $builderDefinition = $this->createMock(Definition::class);
        $builderDefinition
            ->method('getTag')
            ->with(
                $this->logicalOr(
                    $this->equalTo('oro_menu.builder'),
                    $this->equalTo('oro_navigation.item.builder')
                )
            )
            ->willReturn([['alias' => 'test']]);

        $containerMock
            ->method('getDefinition')
            ->willReturnMap([
                ['knp_menu.helper', $menuHelperDefinition],
                ['oro_menu.builder_chain', $definition],
                ['oro_navigation.item.factory', $definition],
                ['service1', $builderDefinition],
                ['service2', $builderDefinition],
            ]);

        $containerMock->expects($this->exactly(2))
            ->method('findTaggedServiceIds')
            ->with(
                $this->logicalOr(
                    $this->equalTo('oro_menu.builder'),
                    $this->equalTo('oro_navigation.item.builder')
                )
            )
            ->willReturn($serviceIds);

        $menuHelperDefinition->expects($this->once())
            ->method('setPublic')
            ->with(true);

        $compilerPass = new MenuBuilderChainPass();
        $compilerPass->process($containerMock);
    }
}
