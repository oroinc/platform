<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\UIBundle\DependencyInjection\Compiler\AbstractGroupingWidgetProviderPass;

abstract class ActionWidgetProviderPassAbstractTest extends \PHPUnit_Framework_TestCase
{
    /** @var AbstractGroupingWidgetProviderPass */
    protected $widgetProvider;

    public function setUp()
    {
        $this->widgetProvider = $this->createTestInstance();
    }

    public function tearDown()
    {
        unset($this->widgetProvider);
    }

    public function testGetChainProviderServiceId()
    {
        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $container */
        $container = $this->getMock('\Symfony\Component\DependencyInjection\ContainerBuilder');
        $container->expects($this->once())
            ->method('hasDefinition')
            ->with($this->getChainProviderServiceId())
            ->willReturn(false);

        $container->expects($this->never())
            ->method('findTaggedServiceIds');
        $this->widgetProvider->process($container);
    }

    public function testGetProviderTagName()
    {
        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $container */
        $container = $this->getMock('\Symfony\Component\DependencyInjection\ContainerBuilder');
        $container->expects($this->once())
            ->method('hasDefinition')
            ->willReturn(true);
        $container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with($this->getProviderTagName())
            ->willReturn([]);

        $this->widgetProvider->process($container);
    }

    /**
     * @return string
     */
    abstract protected function getChainProviderServiceId();

    /**
     * @return string
     */
    abstract protected function getProviderTagName();

    /**
     * @return AbstractGroupingWidgetProviderPass
     */
    abstract protected function createTestInstance();
}
