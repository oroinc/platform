<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\DependencyInjection;

use Oro\Component\Layout\Extension\DependencyInjection\DependencyInjectionExtension;
use Oro\Component\Layout\LayoutItemInterface;

class DependencyInjectionExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    /** @var DependencyInjectionExtension */
    protected $extension;

    protected function setUp()
    {
        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->extension = new DependencyInjectionExtension(
            $this->container,
            ['test' => 'block_type_service'],
            ['test' => ['block_type_extension_service']],
            ['test' => ['layout_update_service']],
            ['context_configurator_service'],
            ['test' => 'data_provider_service']
        );
    }

    public function testHasType()
    {
        $this->assertTrue($this->extension->hasType('test'));
        $this->assertFalse($this->extension->hasType('unknown'));
    }

    public function testGetType()
    {
        $type = $this->getMock('Oro\Component\Layout\BlockTypeInterface');
        $type->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('test'));

        $this->container->expects($this->once())
            ->method('get')
            ->with('block_type_service')
            ->will($this->returnValue($type));

        $this->assertSame($type, $this->extension->getType('test'));
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The type name specified for the service "block_type_service" does not match the actual name. Expected "test", given "test1".
     */
    // @codingStandardsIgnoreEnd
    public function testGetTypeWithInvalidAlias()
    {
        $type = $this->getMock('Oro\Component\Layout\BlockTypeInterface');
        $type->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('test1'));

        $this->container->expects($this->once())
            ->method('get')
            ->with('block_type_service')
            ->will($this->returnValue($type));

        $this->extension->getType('test');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The block type "unknown" is not registered with the service container.
     */
    public function testGetUnknownType()
    {
        $this->extension->getType('unknown');
    }

    public function testHasTypeExtensions()
    {
        $this->assertTrue($this->extension->hasTypeExtensions('test'));
        $this->assertFalse($this->extension->hasTypeExtensions('unknown'));
    }

    public function testGetTypeExtensions()
    {
        $typeExtension = $this->getMock('Oro\Component\Layout\BlockTypeExtensionInterface');

        $this->container->expects($this->once())
            ->method('get')
            ->with('block_type_extension_service')
            ->will($this->returnValue($typeExtension));

        $typeExtensions = $this->extension->getTypeExtensions('test');
        $this->assertCount(1, $typeExtensions);
        $this->assertSame($typeExtension, $typeExtensions[0]);
    }

    public function testGetUnknownBlockTypeExtensions()
    {
        $this->assertSame([], $this->extension->getTypeExtensions('unknown'));
    }

    public function testHasLayoutUpdates()
    {
        $this->assertTrue($this->extension->hasLayoutUpdates($this->getLayoutItem('test')));
        $this->assertFalse($this->extension->hasLayoutUpdates($this->getLayoutItem('unknown')));

        // test by alias
        $layoutItem = $this->getLayoutItem('unknown');
        $layoutItem->expects($this->once())->method('getAlias')->willReturn('test');
        $this->assertTrue($this->extension->hasLayoutUpdates($layoutItem));
    }

    public function testGetLayoutUpdates()
    {
        $layoutUpdate = $this->getMock('Oro\Component\Layout\LayoutUpdateInterface');

        $this->container->expects($this->exactly(2))
            ->method('get')
            ->with('layout_update_service')
            ->will($this->returnValue($layoutUpdate));

        $layoutUpdates = $this->extension->getLayoutUpdates($this->getLayoutItem('test'));
        $this->assertCount(1, $layoutUpdates);
        $this->assertSame($layoutUpdate, $layoutUpdates[0]);

        // test by alias
        $layoutItem = $this->getLayoutItem('unknown');
        $layoutItem->expects($this->once())->method('getAlias')->willReturn('test');
        $this->assertCount(1, $this->extension->getLayoutUpdates($layoutItem));
        $this->assertSame($layoutUpdate, $layoutUpdates[0]);
    }

    public function testGetUnknownBlockLayoutUpdates()
    {
        $this->assertSame([], $this->extension->getLayoutUpdates($this->getLayoutItem('unknown')));
    }

    public function testHasContextConfigurators()
    {
        $this->assertTrue($this->extension->hasContextConfigurators());
    }

    public function testGetContextConfigurators()
    {
        $configurator = $this->getMock('Oro\Component\Layout\ContextConfiguratorInterface');

        $this->container->expects($this->once())
            ->method('get')
            ->with('context_configurator_service')
            ->will($this->returnValue($configurator));

        $result = $this->extension->getContextConfigurators();
        $this->assertCount(1, $result);
        $this->assertSame($configurator, $result[0]);
    }

    public function testHasContextConfiguratorsWhenNoAnyRegistered()
    {
        $extension = new DependencyInjectionExtension($this->container, [], [], [], [], []);

        $this->assertFalse($extension->hasContextConfigurators());
    }

    public function testGetContextConfiguratorsWhenNoAnyRegistered()
    {
        $extension = new DependencyInjectionExtension($this->container, [], [], [], [], []);

        $this->assertSame([], $extension->getContextConfigurators());
    }

    public function testHasDataProvider()
    {
        $this->assertTrue($this->extension->hasDataProvider('test'));
        $this->assertFalse($this->extension->hasDataProvider('unknown'));
    }

    public function testGetDataProvider()
    {
        $dataProvider = $this->getMock(\stdClass::class);

        $this->container->expects($this->once())
            ->method('get')
            ->with('data_provider_service')
            ->will($this->returnValue($dataProvider));

        $this->assertSame($dataProvider, $this->extension->getDataProvider('test'));
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The data provider "unknown" is not registered with the service container.
     */
    public function testGetUnknownDataProvider()
    {
        $this->extension->getDataProvider('unknown');
    }

    /**
     * @param string $id
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|LayoutItemInterface
     */
    protected function getLayoutItem($id)
    {
        $layoutItem = $this->getMock('Oro\Component\Layout\LayoutItemInterface');
        $layoutItem->expects($this->any())->method('getId')->willReturn($id);

        return $layoutItem;
    }
}
