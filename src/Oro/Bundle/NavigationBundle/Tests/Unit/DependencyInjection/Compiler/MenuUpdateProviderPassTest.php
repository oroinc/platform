<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\NavigationBundle\DependencyInjection\Compiler\MenuUpdateProviderPass;

class MenuUpdateProviderPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MenuUpdateProviderPass
     */
    protected $menuUpdateProviderPass;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContainerBuilder
     */
    protected $container;

    protected function setUp()
    {
        $this->container = $this
            ->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();

        $this->menuUpdateProviderPass = new MenuUpdateProviderPass();
    }

    public function testServiceNotExists()
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with($this->equalTo(MenuUpdateProviderPass::BUILDER_SERVICE_ID))
            ->will($this->returnValue(false));

        $this->container->expects($this->never())
            ->method('getDefinition');

        $this->container->expects($this->never())
            ->method('findTaggedServiceIds');

        $this->menuUpdateProviderPass->process($this->container);
    }

    public function testServiceExistsNotTaggedServices()
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with($this->equalTo(MenuUpdateProviderPass::BUILDER_SERVICE_ID))
            ->will($this->returnValue(true));

        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with($this->equalTo(MenuUpdateProviderPass::UPDATE_PROVIDER_TAG))
            ->will($this->returnValue([]));

        $this->container->expects($this->never())
            ->method('getDefinition');

        $this->menuUpdateProviderPass->process($this->container);
    }

    public function testProcess()
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with($this->equalTo(MenuUpdateProviderPass::BUILDER_SERVICE_ID))
            ->will($this->returnValue(true));

        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with($this->equalTo(MenuUpdateProviderPass::UPDATE_PROVIDER_TAG))
            ->will($this->returnValue(['provider' => [['area' => 'test.area']]]));

        $definition = $this->getMock('Symfony\Component\DependencyInjection\Definition');

        $this->container->expects($this->once())
            ->method('getDefinition')
            ->with($this->equalTo(MenuUpdateProviderPass::BUILDER_SERVICE_ID))
            ->will($this->returnValue($definition));

        $definition->expects($this->once())
            ->method('addMethodCall')
            ->with($this->isType('string'), $this->isType('array'));

        $this->menuUpdateProviderPass->process($this->container);
    }
}
