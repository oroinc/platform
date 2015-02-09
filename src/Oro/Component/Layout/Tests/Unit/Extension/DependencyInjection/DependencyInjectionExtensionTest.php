<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\DependencyInjection;

use Oro\Component\Layout\Extension\DependencyInjection\DependencyInjectionExtension;

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
            ['test' => ['layout_update_service']]
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
    public function testGetUnknownBlockType()
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
        $this->assertTrue($this->extension->hasLayoutUpdates('test'));
        $this->assertFalse($this->extension->hasLayoutUpdates('unknown'));
    }

    public function testGetLayoutUpdates()
    {
        $layoutUpdate = $this->getMock('Oro\Component\Layout\LayoutUpdateInterface');

        $this->container->expects($this->once())
            ->method('get')
            ->with('layout_update_service')
            ->will($this->returnValue($layoutUpdate));

        $layoutUpdates = $this->extension->getLayoutUpdates('test');
        $this->assertCount(1, $layoutUpdates);
        $this->assertSame($layoutUpdate, $layoutUpdates[0]);
    }

    public function testGetUnknownBlockLayoutUpdates()
    {
        $this->assertSame([], $this->extension->getLayoutUpdates('unknown'));
    }
}
