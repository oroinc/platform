<?php
namespace Oro\Bundle\NavigationBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\NavigationBundle\DependencyInjection\Compiler\MenuBuilderChainPass;
use Symfony\Component\DependencyInjection\Reference;

class MenuBuilderPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcessSkip()
    {
        $menuHelperDefinition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->getMock();

        $containerMock = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();
        $containerMock->expects($this->exactly(2))
            ->method('hasDefinition')
            ->with(
                $this->logicalOr(
                    $this->equalTo('oro_menu.builder_chain'),
                    $this->equalTo('oro_navigation.item.factory')
                )
            )
            ->will($this->returnValue(false));
        $containerMock->expects($this->once())
            ->method('getDefinition')
            ->with('knp_menu.helper')
            ->willReturn($menuHelperDefinition);
        $containerMock->expects($this->never())
            ->method('findTaggedServiceIds');

        $menuHelperDefinition->expects(self::once())
            ->method('setPublic')
            ->with(true);

        $compilerPass = new MenuBuilderChainPass();
        $compilerPass->process($containerMock);
    }

    public function testProcess()
    {
        $menuHelperDefinition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->getMock();
        $definition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->getMock();
        $definition->expects($this->exactly(4))
            ->method('addMethodCall');
        $definition->expects($this->at(0))
            ->method('addMethodCall')
            ->with('addBuilder', array(new Reference('service1'), 'test'));
        $definition->expects($this->at(1))
            ->method('addMethodCall')
            ->with('addBuilder', array(new Reference('service2'), 'test'));
        $definition->expects($this->at(3))
            ->method('addMethodCall')
            ->with('addBuilder', array(new Reference('service1')));
        $definition->expects($this->at(5))
            ->method('addMethodCall')
            ->with('addBuilder', array(new Reference('service2')));

        $serviceIds = array(
            'service1' => array(array('alias' => 'test')),
            'service2' => array(array('alias' => 'test'))
        );

        $containerMock = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();

        $containerMock->expects($this->exactly(2))
            ->method('hasDefinition')
            ->with(
                $this->logicalOr(
                    $this->equalTo('oro_menu.builder_chain'),
                    $this->equalTo('oro_navigation.item.factory')
                )
            )
            ->will($this->returnValue(true));

        $containerMock->expects($this->exactly(5))
            ->method('getDefinition')
            ->willReturnMap([
                ['knp_menu.helper', $menuHelperDefinition],
                ['oro_menu.builder_chain', $definition],
                ['oro_navigation.item.factory', $definition],
                ['service1', $definition],
                ['service2', $definition],
            ]);

        $containerMock->expects($this->exactly(2))
            ->method('findTaggedServiceIds')
            ->with(
                $this->logicalOr(
                    $this->equalTo('oro_menu.builder'),
                    $this->equalTo('oro_navigation.item.builder')
                )
            )
            ->will($this->returnValue($serviceIds));

        $menuHelperDefinition->expects(self::once())
            ->method('setPublic')
            ->with(true);

        $compilerPass = new MenuBuilderChainPass();
        $compilerPass->process($containerMock);
    }
}
