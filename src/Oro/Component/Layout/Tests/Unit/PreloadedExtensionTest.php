<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\PreloadedExtension;

class PreloadedExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testBlockTypes()
    {
        $name      = 'test';
        $blockType = $this->getMock('Oro\Component\Layout\BlockTypeInterface');

        $extension = new PreloadedExtension(
            [
                $name => $blockType
            ]
        );

        $this->assertTrue($extension->hasBlockType($name));
        $this->assertFalse($extension->hasBlockType('unknown'));

        $this->assertSame($blockType, $extension->getBlockType($name));
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The type "unknown" can not be loaded by this extension.
     */
    public function testGetBlockUnknownType()
    {
        $extension = new PreloadedExtension([]);

        $extension->getBlockType('unknown');
    }

    public function testBlockTypeExtensions()
    {
        $name               = 'test';
        $blockTypeExtension = $this->getMock('Oro\Component\Layout\BlockTypeExtensionInterface');

        $extension = new PreloadedExtension(
            [],
            [
                $name => [$blockTypeExtension]
            ]
        );

        $this->assertTrue($extension->hasBlockTypeExtensions($name));
        $this->assertFalse($extension->hasBlockTypeExtensions('unknown'));

        $this->assertCount(1, $extension->getBlockTypeExtensions($name));
        $this->assertSame($blockTypeExtension, $extension->getBlockTypeExtensions($name)[0]);

        $this->assertSame([], $extension->getBlockTypeExtensions('unknown'));
    }

    public function testLayoutUpdates()
    {
        $id           = 'test';
        $layoutUpdate = $this->getMock('Oro\Component\Layout\LayoutUpdateInterface');

        $extension = new PreloadedExtension(
            [],
            [],
            [
                $id => [$layoutUpdate]
            ]
        );

        $this->assertTrue($extension->hasLayoutUpdates($id));
        $this->assertFalse($extension->hasLayoutUpdates('unknown'));

        $this->assertCount(1, $extension->getLayoutUpdates($id));
        $this->assertSame($layoutUpdate, $extension->getLayoutUpdates($id)[0]);

        $this->assertSame([], $extension->getLayoutUpdates('unknown'));
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage Keys of $blockTypes array must be strings.
     */
    public function testConstructWithInvalidKeysForBlockTypes()
    {
        new PreloadedExtension(
            [
                'test' => $this->getMock('Oro\Component\Layout\BlockTypeInterface'),
                $this->getMock('Oro\Component\Layout\BlockTypeInterface')
            ]
        );
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage Each item of $blockTypes array must be BlockTypeInterface.
     */
    public function testConstructWithInvalidBlockTypes()
    {
        new PreloadedExtension(
            [
                'test1' => $this->getMock('Oro\Component\Layout\BlockTypeInterface'),
                'test2' => new \stdClass()
            ]
        );
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage Keys of $blockTypeExtensions array must be strings.
     */
    public function testConstructWithInvalidKeysForBlockTypeExtensions()
    {
        new PreloadedExtension(
            [],
            [
                'test' => [$this->getMock('Oro\Component\Layout\BlockTypeExtensionInterface')],
                [$this->getMock('Oro\Component\Layout\BlockTypeExtensionInterface')]
            ]
        );
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage Each item of $blockTypeExtensions[] array must be BlockTypeExtensionInterface.
     */
    public function testConstructWithInvalidBlockTypeExtensions()
    {
        new PreloadedExtension(
            [],
            [
                'test1' => [$this->getMock('Oro\Component\Layout\BlockTypeExtensionInterface')],
                'test2' => [new \stdClass()]
            ]
        );
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage Each item of $blockTypeExtensions array must be array of BlockTypeExtensionInterface.
     */
    public function testConstructWithSingleBlockTypeExtensions()
    {
        new PreloadedExtension(
            [],
            [
                'test' => $this->getMock('Oro\Component\Layout\BlockTypeExtensionInterface')
            ]
        );
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage Keys of $layoutUpdates array must be strings.
     */
    public function testConstructWithInvalidKeysForLayoutUpdates()
    {
        new PreloadedExtension(
            [],
            [],
            [
                'test' => [$this->getMock('Oro\Component\Layout\LayoutUpdateInterface')],
                [$this->getMock('Oro\Component\Layout\LayoutUpdateInterface')]
            ]
        );
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage Each item of $layoutUpdates[] array must be LayoutUpdateInterface.
     */
    public function testConstructWithInvalidLayoutUpdates()
    {
        new PreloadedExtension(
            [],
            [],
            [
                'test1' => [$this->getMock('Oro\Component\Layout\LayoutUpdateInterface')],
                'test2' => [new \stdClass()]
            ]
        );
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage Each item of $layoutUpdates array must be array of LayoutUpdateInterface.
     */
    public function testConstructWithSingleLayoutUpdates()
    {
        new PreloadedExtension(
            [],
            [],
            [
                'test' => $this->getMock('Oro\Component\Layout\LayoutUpdateInterface')
            ]
        );
    }
}
