<?php

namespace Oro\Component\Layout\Tests\Unit\Extension;

use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\Tests\Unit\Fixtures\AbstractExtensionStub;

class AbstractExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testHasType()
    {
        $extension = $this->getAbstractExtension();
        $this->assertTrue($extension->hasType('test'));
        $this->assertFalse($extension->hasType('unknown'));
    }

    public function testGetType()
    {
        $extension = $this->getAbstractExtension();
        $this->assertInstanceOf(
            'Oro\Component\Layout\BlockTypeInterface',
            $extension->getType('test')
        );
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The block type "unknown" can not be loaded by this extension.
     */
    public function testGetUnknownType()
    {
        $extension = $this->getAbstractExtension();
        $extension->getType('unknown');
    }

    public function testHasTypeExtensions()
    {
        $extension = $this->getAbstractExtension();
        $this->assertTrue($extension->hasTypeExtensions('test'));
        $this->assertFalse($extension->hasTypeExtensions('unknown'));
    }

    public function testGetTypeExtensions()
    {
        $extension = $this->getAbstractExtension();
        $this->assertCount(1, $extension->getTypeExtensions('test'));
        $this->assertInstanceOf(
            'Oro\Component\Layout\BlockTypeExtensionInterface',
            $extension->getTypeExtensions('test')[0]
        );
        $this->assertSame([], $extension->getTypeExtensions('unknown'));
    }

    public function testHasLayoutUpdates()
    {
        $extension = $this->getAbstractExtension();
        $this->assertTrue($extension->hasLayoutUpdates($this->getLayoutItem('test')));
        $this->assertFalse($extension->hasLayoutUpdates($this->getLayoutItem('unknown')));

        // test by alias
        $layoutItem = $this->getLayoutItem('unknown');
        $layoutItem->expects($this->once())->method('getAlias')->willReturn('test');
        $this->assertTrue($extension->hasLayoutUpdates($layoutItem));
    }

    public function testGetLayoutUpdates()
    {
        $layoutItem = $this->getLayoutItem('test');

        $extension     = $this->getAbstractExtension();
        $layoutUpdates = $extension->getLayoutUpdates($layoutItem);
        $this->assertCount(1, $layoutUpdates);
        $this->assertInstanceOf('Oro\Component\Layout\LayoutUpdateInterface', $layoutUpdates[0]);

        $this->assertSame([], $extension->getLayoutUpdates($this->getLayoutItem('unknown')));

        // test by alias
        $layoutItem = $this->getLayoutItem('unknown');
        $layoutItem->expects($this->once())->method('getAlias')->willReturn('test');
        $layoutUpdates = $extension->getLayoutUpdates($layoutItem);
        $this->assertCount(1, $layoutUpdates);
        $this->assertInstanceOf('Oro\Component\Layout\LayoutUpdateInterface', $layoutUpdates[0]);
    }

    public function testHasContextConfigurators()
    {
        $extension = $this->getAbstractExtension();
        $this->assertTrue($extension->hasContextConfigurators());
    }

    public function testGetContextConfigurators()
    {
        $extension     = $this->getAbstractExtension();
        $configurators = $extension->getContextConfigurators();
        $this->assertCount(1, $configurators);
        $this->assertInstanceOf(
            'Oro\Component\Layout\ContextConfiguratorInterface',
            $configurators[0]
        );
    }

    public function testHasContextConfiguratorsWhenNoAnyRegistered()
    {
        $extension = new AbstractExtensionStub([], [], [], [], []);

        $this->assertFalse($extension->hasContextConfigurators());
    }

    public function testGetContextConfiguratorsWhenNoAnyRegistered()
    {
        $extension = new AbstractExtensionStub([], [], [], [], []);

        $this->assertSame([], $extension->getContextConfigurators());
    }

    public function testHasDataProvider()
    {
        $extension = $this->getAbstractExtension();
        $this->assertTrue($extension->hasDataProvider('test'));
        $this->assertFalse($extension->hasDataProvider('unknown'));
    }

    public function testGetDataProvider()
    {
        $extension = $this->getAbstractExtension();
        $this->assertTrue(is_object($extension->getDataProvider('test')));
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The data provider "unknown" can not be loaded by this extension.
     */
    public function testGetUnknownDataProvider()
    {
        $extension = $this->getAbstractExtension();
        $extension->getDataProvider('unknown');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "Oro\Component\Layout\BlockTypeExtensionInterface", "integer" given.
     */
    // @codingStandardsIgnoreEnd
    public function testLoadInvalidBlockTypeExtensions()
    {
        $extension = new AbstractExtensionStub([], [123], [], [], []);
        $extension->hasTypeExtensions('test');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "Oro\Component\Layout\LayoutUpdateInterface", "integer" given.
     */
    // @codingStandardsIgnoreEnd
    public function testLoadInvalidLayoutUpdates()
    {
        $extension = new AbstractExtensionStub([], [], ['test' => [123]], [], []);
        $extension->hasLayoutUpdates($this->getLayoutItem('test'));
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Invalid "layout item id" argument type. Expected "string", "integer" given.
     */
    public function testLoadLayoutUpdatesWithInvalidId()
    {
        $extension = new AbstractExtensionStub(
            [],
            [],
            [
                [$this->getMock('Oro\Component\Layout\LayoutUpdateInterface')]
            ],
            [],
            []
        );
        $extension->hasLayoutUpdates($this->getLayoutItem('test'));
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Invalid "layout updates for item "test"" argument type. Expected "array",
     */
    // @codingStandardsIgnoreEnd
    public function testLoadLayoutUpdatesWithInvalidFormatOfReturnedData()
    {
        $extension = new AbstractExtensionStub(
            [],
            [],
            [
                'test' => $this->getMock('Oro\Component\Layout\LayoutUpdateInterface')
            ],
            [],
            []
        );
        $extension->hasLayoutUpdates($this->getLayoutItem('test'));
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "Oro\Component\Layout\ContextConfiguratorInterface", "integer" given.
     */
    // @codingStandardsIgnoreEnd
    public function testLoadInvalidContextConfigurators()
    {
        $extension = new AbstractExtensionStub([], [], [], [123], []);
        $extension->hasContextConfigurators();
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "Oro\Component\Layout\BlockTypeInterface", "integer" given.
     */
    public function testLoadInvalidBlockTypes()
    {
        $extension = new AbstractExtensionStub([123], [], [], [], []);
        $extension->hasType('test');
    }

    protected function getAbstractExtension()
    {
        $type = $this->getMock('Oro\Component\Layout\BlockTypeInterface');
        $type->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('test'));

        $extension = $this->getMock('Oro\Component\Layout\BlockTypeExtensionInterface');
        $extension->expects($this->any())
            ->method('getExtendedType')
            ->will($this->returnValue('test'));

        return new AbstractExtensionStub(
            [$type],
            [$extension],
            [
                'test' => [
                    $this->getMock('Oro\Component\Layout\LayoutUpdateInterface')
                ]
            ],
            [$this->getMock('Oro\Component\Layout\ContextConfiguratorInterface')],
            ['test' => $this->getMock(\stdClass::class)]
        );
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
        $layoutItem->expects($this->any())->method('getContext')
            ->willReturn($this->getMock('Oro\Component\Layout\ContextInterface'));

        return $layoutItem;
    }
}
