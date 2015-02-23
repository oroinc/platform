<?php

namespace Oro\Component\Layout\Tests\Unit\Extension;

use Oro\Component\Layout\Extension\PreloadedExtension;

class PreloadedExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testBlockTypes()
    {
        $name = 'test';
        $type = $this->getMock('Oro\Component\Layout\BlockTypeInterface');

        $extension = new PreloadedExtension(
            [
                $name => $type
            ]
        );

        $this->assertTrue($extension->hasType($name));
        $this->assertFalse($extension->hasType('unknown'));

        $this->assertSame($type, $extension->getType($name));
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The type "unknown" can not be loaded by this extension.
     */
    public function testGetBlockUnknownType()
    {
        $extension = new PreloadedExtension([]);

        $extension->getType('unknown');
    }

    public function testBlockTypeExtensions()
    {
        $name          = 'test';
        $typeExtension = $this->getMock('Oro\Component\Layout\BlockTypeExtensionInterface');

        $extension = new PreloadedExtension(
            [],
            [
                $name => [$typeExtension]
            ]
        );

        $this->assertTrue($extension->hasTypeExtensions($name));
        $this->assertFalse($extension->hasTypeExtensions('unknown'));

        $this->assertCount(1, $extension->getTypeExtensions($name));
        $this->assertSame($typeExtension, $extension->getTypeExtensions($name)[0]);

        $this->assertSame([], $extension->getTypeExtensions('unknown'));
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

    public function testContextConfigurators()
    {
        $configurator = $this->getMock('Oro\Component\Layout\ContextConfiguratorInterface');

        $extension = new PreloadedExtension(
            [],
            [],
            [],
            [$configurator]
        );

        $this->assertTrue($extension->hasContextConfigurators());

        $result = $extension->getContextConfigurators();
        $this->assertCount(1, $result);
        $this->assertSame($configurator, $result[0]);
    }

    public function testContextConfiguratorsWheNoAnyRegistered()
    {
        $extension = new PreloadedExtension([]);

        $this->assertFalse($extension->hasContextConfigurators());
        $this->assertSame([], $extension->getContextConfigurators());
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage Keys of $types array must be strings.
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
     * @expectedExceptionMessage Each item of $types array must be BlockTypeInterface.
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
     * @expectedExceptionMessage Keys of $typeExtensions array must be strings.
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
     * @expectedExceptionMessage Each item of $typeExtensions[] array must be BlockTypeExtensionInterface.
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
     * @expectedExceptionMessage Each item of $typeExtensions array must be array of BlockTypeExtensionInterface.
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

    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage Each item of $contextConfigurators array must be ContextConfiguratorInterface.
     */
    public function testConstructWithInvalidContextConfiguratorType()
    {
        new PreloadedExtension(
            [],
            [],
            [],
            [
                $this->getMock('Oro\Component\Layout\ContextConfiguratorInterface'),
                new \stdClass()
            ]
        );
    }
}
