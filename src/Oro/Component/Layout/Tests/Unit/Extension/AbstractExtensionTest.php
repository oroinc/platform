<?php

namespace Oro\Component\Layout\Tests\Unit\Extension;

use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\Tests\Unit\Fixtures\AbstractExtensionStub;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AbstractExtensionTest extends \PHPUnit\Framework\TestCase
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

    public function testGetUnknownType()
    {
        $this->expectException(\Oro\Component\Layout\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('The block type "unknown" can not be loaded by this extension.');

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

    public function testGetUnknownDataProvider()
    {
        $this->expectException(\Oro\Component\Layout\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('The data provider "unknown" can not be loaded by this extension.');

        $extension = $this->getAbstractExtension();
        $extension->getDataProvider('unknown');
    }

    public function testLoadInvalidBlockTypeExtensions()
    {
        $this->expectException(\Oro\Component\Layout\Exception\UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Oro\Component\Layout\BlockTypeExtensionInterface", "integer" given.'
        );

        $extension = new AbstractExtensionStub([], [123], [], [], []);
        $extension->hasTypeExtensions('test');
    }

    public function testLoadInvalidLayoutUpdates()
    {
        $this->expectException(\Oro\Component\Layout\Exception\UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Oro\Component\Layout\LayoutUpdateInterface", "integer" given.'
        );

        $extension = new AbstractExtensionStub([], [], ['test' => [123]], [], []);
        $extension->hasLayoutUpdates($this->getLayoutItem('test'));
    }

    public function testLoadLayoutUpdatesWithInvalidId()
    {
        $this->expectException(\Oro\Component\Layout\Exception\UnexpectedTypeException::class);
        $this->expectExceptionMessage('Invalid "layout item id" argument type. Expected "string", "integer" given.');

        $extension = new AbstractExtensionStub(
            [],
            [],
            [
                [$this->createMock('Oro\Component\Layout\LayoutUpdateInterface')]
            ],
            [],
            []
        );
        $extension->hasLayoutUpdates($this->getLayoutItem('test'));
    }

    public function testLoadLayoutUpdatesWithInvalidFormatOfReturnedData()
    {
        $this->expectException(\Oro\Component\Layout\Exception\UnexpectedTypeException::class);
        $this->expectExceptionMessage('Invalid "layout updates for item "test"" argument type. Expected "array",');

        $extension = new AbstractExtensionStub(
            [],
            [],
            [
                'test' => $this->createMock('Oro\Component\Layout\LayoutUpdateInterface')
            ],
            [],
            []
        );
        $extension->hasLayoutUpdates($this->getLayoutItem('test'));
    }

    public function testLoadInvalidContextConfigurators()
    {
        $this->expectException(\Oro\Component\Layout\Exception\UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Oro\Component\Layout\ContextConfiguratorInterface", "integer" given.'
        );

        $extension = new AbstractExtensionStub([], [], [], [123], []);
        $extension->hasContextConfigurators();
    }

    public function testLoadInvalidBlockTypes()
    {
        $this->expectException(\Oro\Component\Layout\Exception\UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Oro\Component\Layout\BlockTypeInterface", "integer" given.'
        );

        $extension = new AbstractExtensionStub([123], [], [], [], []);
        $extension->hasType('test');
    }

    protected function getAbstractExtension()
    {
        $type = $this->createMock('Oro\Component\Layout\BlockTypeInterface');
        $type->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('test'));

        $extension = $this->createMock('Oro\Component\Layout\BlockTypeExtensionInterface');
        $extension->expects($this->any())
            ->method('getExtendedType')
            ->will($this->returnValue('test'));

        return new AbstractExtensionStub(
            [$type],
            [$extension],
            [
                'test' => [
                    $this->createMock('Oro\Component\Layout\LayoutUpdateInterface')
                ]
            ],
            [$this->createMock('Oro\Component\Layout\ContextConfiguratorInterface')],
            ['test' => $this->createMock(\stdClass::class)]
        );
    }

    /**
     * @param string $id
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|LayoutItemInterface
     */
    protected function getLayoutItem($id)
    {
        $layoutItem = $this->createMock('Oro\Component\Layout\LayoutItemInterface');
        $layoutItem->expects($this->any())->method('getId')->willReturn($id);
        $layoutItem->expects($this->any())->method('getContext')
            ->willReturn($this->createMock('Oro\Component\Layout\ContextInterface'));

        return $layoutItem;
    }
}
