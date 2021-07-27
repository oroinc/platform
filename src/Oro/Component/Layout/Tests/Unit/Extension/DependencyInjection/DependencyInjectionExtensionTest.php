<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\DependencyInjection;

use Oro\Component\Layout\BlockTypeExtensionInterface;
use Oro\Component\Layout\BlockTypeInterface;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\Exception\InvalidArgumentException;
use Oro\Component\Layout\Extension\DependencyInjection\DependencyInjectionExtension;
use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\LayoutUpdateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DependencyInjectionExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $container;

    /** @var DependencyInjectionExtension */
    private $extension;

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

    public function testGetTypeNames()
    {
        $this->assertEquals(['test'], $this->extension->getTypeNames());
    }

    public function testHasType()
    {
        $this->assertTrue($this->extension->hasType('test'));
        $this->assertFalse($this->extension->hasType('unknown'));
    }

    public function testGetType()
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

    public function testGetTypeWithInvalidAlias()
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

    public function testGetUnknownType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The block type "unknown" is not registered with the service container.');

        $this->extension->getType('unknown');
    }

    public function testHasTypeExtensions()
    {
        $this->assertTrue($this->extension->hasTypeExtensions('test'));
        $this->assertFalse($this->extension->hasTypeExtensions('unknown'));
    }

    public function testGetTypeExtensions()
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
        $layoutItem->expects($this->once())
            ->method('getAlias')
            ->willReturn('test');
        $this->assertTrue($this->extension->hasLayoutUpdates($layoutItem));
    }

    public function testGetLayoutUpdates()
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
        $configurator = $this->createMock(ContextConfiguratorInterface::class);

        $this->container->expects($this->once())
            ->method('get')
            ->with('context_configurator_service')
            ->willReturn($configurator);

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
        $dataProvider = $this->createMock(\stdClass::class);

        $this->container->expects($this->once())
            ->method('get')
            ->with('data_provider_service')
            ->willReturn($dataProvider);

        $this->assertSame($dataProvider, $this->extension->getDataProvider('test'));
    }

    public function testGetUnknownDataProvider()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The data provider "unknown" is not registered with the service container.');

        $this->extension->getDataProvider('unknown');
    }

    /**
     * @return LayoutItemInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getLayoutItem(string $id)
    {
        $layoutItem = $this->createMock(LayoutItemInterface::class);
        $layoutItem->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        return $layoutItem;
    }
}
