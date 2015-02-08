<?php

namespace Oro\Component\Layout\Tests\Unit\Extension;

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
    public function testGetUnknownBlockType()
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
        $extension->hasType('test');
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
