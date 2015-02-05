<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\Tests\Unit\Fixtures\AbstractExtensionStub;

class AbstractExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testHasBlockType()
    {
        $extension = $this->getAbstractExtension();
        $this->assertTrue($extension->hasBlockType('test'));
        $this->assertFalse($extension->hasBlockType('unknown'));
    }

    public function testGetBlockType()
    {
        $extension = $this->getAbstractExtension();
        $this->assertInstanceOf(
            'Oro\Component\Layout\BlockTypeInterface',
            $extension->getBlockType('test')
        );
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The block type "unknown" can not be loaded by this extension.
     */
    public function testGetUnknownBlockType()
    {
        $extension = $this->getAbstractExtension();
        $extension->getBlockType('unknown');
    }

    public function testHasBlockTypeExtensions()
    {
        $extension = $this->getAbstractExtension();
        $this->assertTrue($extension->hasBlockTypeExtensions('test'));
        $this->assertFalse($extension->hasBlockTypeExtensions('unknown'));
    }

    public function testGetBlockTypeExtensions()
    {
        $extension = $this->getAbstractExtension();
        $this->assertCount(1, $extension->getBlockTypeExtensions('test'));
        $this->assertInstanceOf(
            'Oro\Component\Layout\BlockTypeExtensionInterface',
            $extension->getBlockTypeExtensions('test')[0]
        );
        $this->assertSame([], $extension->getBlockTypeExtensions('unknown'));
    }

    public function testHasLayoutUpdates()
    {
        $extension = $this->getAbstractExtension();
        $this->assertTrue($extension->hasLayoutUpdates('test'));
        $this->assertFalse($extension->hasLayoutUpdates('unknown'));
    }

    public function testGetLayoutUpdates()
    {
        $extension = $this->getAbstractExtension();
        $this->assertCount(1, $extension->getLayoutUpdates('test'));
        $this->assertInstanceOf(
            'Oro\Component\Layout\LayoutUpdateInterface',
            $extension->getLayoutUpdates('test')[0]
        );
        $this->assertSame([], $extension->getLayoutUpdates('unknown'));
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "Oro\Component\Layout\BlockTypeInterface", "integer" given.
     */
    public function testLoadInvalidBlockTypes()
    {
        $extension = new AbstractExtensionStub([123], [], []);
        $extension->hasBlockType('test');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "Oro\Component\Layout\BlockTypeExtensionInterface", "integer" given.
     */
    // @codingStandardsIgnoreEnd
    public function testLoadInvalidBlockTypeExtensions()
    {
        $extension = new AbstractExtensionStub([], [123], []);
        $extension->hasBlockTypeExtensions('test');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "Oro\Component\Layout\LayoutUpdateInterface", "integer" given.
     */
    // @codingStandardsIgnoreEnd
    public function testLoadInvalidLayoutUpdates()
    {
        $extension = new AbstractExtensionStub([], [], ['test' => [123]]);
        $extension->hasLayoutUpdates('test');
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
            ]
        );
        $extension->hasLayoutUpdates('test');
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
            ]
        );
        $extension->hasLayoutUpdates('test');
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
            ]
        );
    }
}
