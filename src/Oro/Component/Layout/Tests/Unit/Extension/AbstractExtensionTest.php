<?php

namespace Oro\Component\Layout\Tests\Unit\Extension;

use Oro\Component\Layout\BlockTypeExtensionInterface;
use Oro\Component\Layout\BlockTypeInterface;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Exception\InvalidArgumentException;
use Oro\Component\Layout\Exception\UnexpectedTypeException;
use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\LayoutUpdateInterface;
use Oro\Component\Layout\Tests\Unit\Fixtures\AbstractExtensionStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AbstractExtensionTest extends TestCase
{
    public function testGetTypeNames(): void
    {
        $extension = $this->getAbstractExtension();
        $this->assertEquals(['test'], $extension->getTypeNames());
    }

    public function testHasType(): void
    {
        $extension = $this->getAbstractExtension();
        $this->assertTrue($extension->hasType('test'));
        $this->assertFalse($extension->hasType('unknown'));
    }

    public function testGetType(): void
    {
        $extension = $this->getAbstractExtension();
        $this->assertInstanceOf(BlockTypeInterface::class, $extension->getType('test'));
    }

    public function testGetUnknownType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The block type "unknown" can not be loaded by this extension.');

        $extension = $this->getAbstractExtension();
        $extension->getType('unknown');
    }

    public function testHasTypeExtensions(): void
    {
        $extension = $this->getAbstractExtension();
        $this->assertTrue($extension->hasTypeExtensions('test'));
        $this->assertFalse($extension->hasTypeExtensions('unknown'));
    }

    public function testGetTypeExtensions(): void
    {
        $extension = $this->getAbstractExtension();
        $this->assertCount(1, $extension->getTypeExtensions('test'));
        $this->assertInstanceOf(BlockTypeExtensionInterface::class, $extension->getTypeExtensions('test')[0]);
        $this->assertSame([], $extension->getTypeExtensions('unknown'));
    }

    public function testHasLayoutUpdates(): void
    {
        $extension = $this->getAbstractExtension();
        $this->assertTrue($extension->hasLayoutUpdates($this->getLayoutItem('test')));
        $this->assertFalse($extension->hasLayoutUpdates($this->getLayoutItem('unknown')));

        // test by alias
        $layoutItem = $this->getLayoutItem('unknown');
        $layoutItem->expects($this->once())
            ->method('getAlias')
            ->willReturn('test');
        $this->assertTrue($extension->hasLayoutUpdates($layoutItem));
    }

    public function testGetLayoutUpdates(): void
    {
        $layoutItem = $this->getLayoutItem('test');

        $extension = $this->getAbstractExtension();
        $layoutUpdates = $extension->getLayoutUpdates($layoutItem);
        $this->assertCount(1, $layoutUpdates);
        $this->assertInstanceOf(LayoutUpdateInterface::class, $layoutUpdates[0]);

        $this->assertSame([], $extension->getLayoutUpdates($this->getLayoutItem('unknown')));

        // test by alias
        $layoutItem = $this->getLayoutItem('unknown');
        $layoutItem->expects($this->once())
            ->method('getAlias')
            ->willReturn('test');
        $layoutUpdates = $extension->getLayoutUpdates($layoutItem);
        $this->assertCount(1, $layoutUpdates);
        $this->assertInstanceOf(LayoutUpdateInterface::class, $layoutUpdates[0]);
    }

    public function testHasContextConfigurators(): void
    {
        $extension = $this->getAbstractExtension();
        $this->assertTrue($extension->hasContextConfigurators());
    }

    public function testGetContextConfigurators(): void
    {
        $extension = $this->getAbstractExtension();
        $configurators = $extension->getContextConfigurators();
        $this->assertCount(1, $configurators);
        $this->assertInstanceOf(ContextConfiguratorInterface::class, $configurators[0]);
    }

    public function testHasContextConfiguratorsWhenNoAnyRegistered(): void
    {
        $extension = new AbstractExtensionStub([], [], [], [], []);

        $this->assertFalse($extension->hasContextConfigurators());
    }

    public function testGetContextConfiguratorsWhenNoAnyRegistered(): void
    {
        $extension = new AbstractExtensionStub([], [], [], [], []);

        $this->assertSame([], $extension->getContextConfigurators());
    }

    public function testHasDataProvider(): void
    {
        $extension = $this->getAbstractExtension();
        $this->assertTrue($extension->hasDataProvider('test'));
        $this->assertFalse($extension->hasDataProvider('unknown'));
    }

    public function testGetDataProvider(): void
    {
        $extension = $this->getAbstractExtension();
        $this->assertIsObject($extension->getDataProvider('test'));
    }

    public function testGetUnknownDataProvider(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The data provider "unknown" can not be loaded by this extension.');

        $extension = $this->getAbstractExtension();
        $extension->getDataProvider('unknown');
    }

    public function testLoadInvalidBlockTypeExtensions(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Oro\Component\Layout\BlockTypeExtensionInterface", "integer" given.'
        );

        $extension = new AbstractExtensionStub([], [123], [], [], []);
        $extension->hasTypeExtensions('test');
    }

    public function testLoadInvalidLayoutUpdates(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Oro\Component\Layout\LayoutUpdateInterface", "integer" given.'
        );

        $extension = new AbstractExtensionStub([], [], ['test' => [123]], [], []);
        $extension->hasLayoutUpdates($this->getLayoutItem('test'));
    }

    public function testLoadLayoutUpdatesWithInvalidId(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Invalid "layout item id" argument type. Expected "string", "integer" given.');

        $extension = new AbstractExtensionStub(
            [],
            [],
            [
                [$this->createMock(LayoutUpdateInterface::class)]
            ],
            [],
            []
        );
        $extension->hasLayoutUpdates($this->getLayoutItem('test'));
    }

    public function testLoadLayoutUpdatesWithInvalidFormatOfReturnedData(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Invalid "layout updates for item "test"" argument type. Expected "array",');

        $extension = new AbstractExtensionStub(
            [],
            [],
            [
                'test' => $this->createMock(LayoutUpdateInterface::class)
            ],
            [],
            []
        );
        $extension->hasLayoutUpdates($this->getLayoutItem('test'));
    }

    public function testLoadInvalidContextConfigurators(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Oro\Component\Layout\ContextConfiguratorInterface", "integer" given.'
        );

        $extension = new AbstractExtensionStub([], [], [], [123], []);
        $extension->hasContextConfigurators();
    }

    public function testLoadInvalidBlockTypes(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Oro\Component\Layout\BlockTypeInterface", "integer" given.'
        );

        $extension = new AbstractExtensionStub([123], [], [], [], []);
        $extension->hasType('test');
    }

    private function getAbstractExtension(): AbstractExtensionStub
    {
        $type = $this->createMock(BlockTypeInterface::class);
        $type->expects($this->any())
            ->method('getName')
            ->willReturn('test');

        $extension = $this->createMock(BlockTypeExtensionInterface::class);
        $extension->expects($this->any())
            ->method('getExtendedType')
            ->willReturn('test');

        return new AbstractExtensionStub(
            [$type],
            [$extension],
            [
                'test' => [
                    $this->createMock(LayoutUpdateInterface::class)
                ]
            ],
            [$this->createMock(ContextConfiguratorInterface::class)],
            ['test' => $this->createMock(\stdClass::class)]
        );
    }

    private function getLayoutItem(string $id): LayoutItemInterface&MockObject
    {
        $layoutItem = $this->createMock(LayoutItemInterface::class);
        $layoutItem->expects($this->any())
            ->method('getId')
            ->willReturn($id);
        $layoutItem->expects($this->any())
            ->method('getContext')
            ->willReturn($this->createMock(ContextInterface::class));

        return $layoutItem;
    }
}
