<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\DependencyInjection;

use Oro\Component\Layout\BlockTypeExtensionInterface;
use Oro\Component\Layout\BlockTypeInterface;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\Exception\InvalidArgumentException;
use Oro\Component\Layout\Extension\DependencyInjection\DependencyInjectionExtension;
use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\LayoutUpdateInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DependencyInjectionExtensionTest extends TestCase
{
    private ContainerInterface&MockObject $container;
    private DependencyInjectionExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->extension = new DependencyInjectionExtension(
            $this->container,
            ['test' => 'block_type_service'],
            ['test' => ['block_type_extension_service']],
            ['test' => ['layout_update_service']],
            ['context_configurator_service'],
            ['test' => 'data_provider_service']
        );
    }

    public function testGetTypeNames(): void
    {
        $this->assertEquals(['test'], $this->extension->getTypeNames());
    }

    public function testHasType(): void
    {
        $this->assertTrue($this->extension->hasType('test'));
        $this->assertFalse($this->extension->hasType('unknown'));
    }

    public function testGetType(): void
    {
        $type = $this->createMock(BlockTypeInterface::class);
        $type->expects($this->once())
            ->method('getName')
            ->willReturn('test');

        $this->container->expects($this->once())
            ->method('get')
            ->with('block_type_service')
            ->willReturn($type);

        $this->assertSame($type, $this->extension->getType('test'));
    }

    public function testGetTypeWithInvalidAlias(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The type name specified for the service "block_type_service" does not match the actual name.'
            . ' Expected "test", given "test1".'
        );

        $type = $this->createMock(BlockTypeInterface::class);
        $type->expects($this->any())
            ->method('getName')
            ->willReturn('test1');

        $this->container->expects($this->once())
            ->method('get')
            ->with('block_type_service')
            ->willReturn($type);

        $this->extension->getType('test');
    }

    public function testGetUnknownType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The block type "unknown" is not registered with the service container.');

        $this->extension->getType('unknown');
    }

    public function testHasTypeExtensions(): void
    {
        $this->assertTrue($this->extension->hasTypeExtensions('test'));
        $this->assertFalse($this->extension->hasTypeExtensions('unknown'));
    }

    public function testGetTypeExtensions(): void
    {
        $typeExtension = $this->createMock(BlockTypeExtensionInterface::class);

        $this->container->expects($this->once())
            ->method('get')
            ->with('block_type_extension_service')
            ->willReturn($typeExtension);

        $typeExtensions = $this->extension->getTypeExtensions('test');
        $this->assertCount(1, $typeExtensions);
        $this->assertSame($typeExtension, $typeExtensions[0]);
    }

    public function testGetUnknownBlockTypeExtensions(): void
    {
        $this->assertSame([], $this->extension->getTypeExtensions('unknown'));
    }

    public function testHasLayoutUpdates(): void
    {
        $this->assertTrue($this->extension->hasLayoutUpdates($this->getLayoutItem('test')));
        $this->assertFalse($this->extension->hasLayoutUpdates($this->getLayoutItem('unknown')));

        // test by alias
        $layoutItem = $this->getLayoutItem('unknown');
        $layoutItem->expects($this->once())
            ->method('getAlias')
            ->willReturn('test');
        $this->assertTrue($this->extension->hasLayoutUpdates($layoutItem));
    }

    public function testGetLayoutUpdates(): void
    {
        $layoutUpdate = $this->createMock(LayoutUpdateInterface::class);

        $this->container->expects($this->exactly(2))
            ->method('get')
            ->with('layout_update_service')
            ->willReturn($layoutUpdate);

        $layoutUpdates = $this->extension->getLayoutUpdates($this->getLayoutItem('test'));
        $this->assertCount(1, $layoutUpdates);
        $this->assertSame($layoutUpdate, $layoutUpdates[0]);

        // test by alias
        $layoutItem = $this->getLayoutItem('unknown');
        $layoutItem->expects($this->once())
            ->method('getAlias')
            ->willReturn('test');
        $this->assertCount(1, $this->extension->getLayoutUpdates($layoutItem));
        $this->assertSame($layoutUpdate, $layoutUpdates[0]);
    }

    public function testGetUnknownBlockLayoutUpdates(): void
    {
        $this->assertSame([], $this->extension->getLayoutUpdates($this->getLayoutItem('unknown')));
    }

    public function testHasContextConfigurators(): void
    {
        $this->assertTrue($this->extension->hasContextConfigurators());
    }

    public function testGetContextConfigurators(): void
    {
        $configurator = $this->createMock(ContextConfiguratorInterface::class);

        $this->container->expects($this->once())
            ->method('get')
            ->with('context_configurator_service')
            ->willReturn($configurator);

        $result = $this->extension->getContextConfigurators();
        $this->assertCount(1, $result);
        $this->assertSame($configurator, $result[0]);
    }

    public function testHasContextConfiguratorsWhenNoAnyRegistered(): void
    {
        $extension = new DependencyInjectionExtension($this->container, [], [], [], [], []);

        $this->assertFalse($extension->hasContextConfigurators());
    }

    public function testGetContextConfiguratorsWhenNoAnyRegistered(): void
    {
        $extension = new DependencyInjectionExtension($this->container, [], [], [], [], []);

        $this->assertSame([], $extension->getContextConfigurators());
    }

    public function testHasDataProvider(): void
    {
        $this->assertTrue($this->extension->hasDataProvider('test'));
        $this->assertFalse($this->extension->hasDataProvider('unknown'));
    }

    public function testGetDataProvider(): void
    {
        $dataProvider = $this->createMock(\stdClass::class);

        $this->container->expects($this->once())
            ->method('get')
            ->with('data_provider_service')
            ->willReturn($dataProvider);

        $this->assertSame($dataProvider, $this->extension->getDataProvider('test'));
    }

    public function testGetUnknownDataProvider(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The data provider "unknown" is not registered with the service container.');

        $this->extension->getDataProvider('unknown');
    }

    private function getLayoutItem(string $id): LayoutItemInterface&MockObject
    {
        $layoutItem = $this->createMock(LayoutItemInterface::class);
        $layoutItem->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        return $layoutItem;
    }
}
