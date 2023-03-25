<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockBuilderInterface;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockTypeExtensionInterface;
use Oro\Component\Layout\BlockTypeInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Exception\InvalidArgumentException;
use Oro\Component\Layout\Exception\UnexpectedTypeException;
use Oro\Component\Layout\Extension\Core\CoreExtension;
use Oro\Component\Layout\Extension\ExtensionInterface;
use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\LayoutManipulatorInterface;
use Oro\Component\Layout\LayoutRegistry;
use Oro\Component\Layout\LayoutUpdateInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LayoutRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExtensionInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $extension;

    /** @var LayoutRegistry */
    private $registry;

    protected function setUp(): void
    {
        $this->extension = $this->createMock(ExtensionInterface::class);

        $this->registry = new LayoutRegistry();
        $this->registry->addExtension(new CoreExtension());
        $this->registry->addExtension($this->extension);
    }

    public function testGetTypeNames()
    {
        $this->extension->expects($this->once())
            ->method('getTypeNames')
            ->willReturn(['type1']);

        $this->assertEquals(
            ['block', 'container', 'type1'],
            $this->registry->getTypeNames()
        );
    }

    public function testGetTypeFromCoreExtension()
    {
        $this->extension->expects($this->never())
            ->method('hasType')
            ->with(BaseType::NAME);
        $this->extension->expects($this->never())
            ->method('getType')
            ->with(BaseType::NAME);

        $type = $this->registry->getType(BaseType::NAME);
        $this->assertInstanceOf(BaseType::class, $type);
    }

    public function testGetType()
    {
        $name = 'test';
        $type = $this->createMock(BlockTypeInterface::class);

        $this->extension->expects($this->once())
            ->method('hasType')
            ->with($name)
            ->willReturn(true);
        $this->extension->expects($this->once())
            ->method('getType')
            ->with($name)
            ->willReturn($type);

        $this->assertSame($type, $this->registry->getType($name));
        // check that the loaded block type is cached
        $this->assertSame($type, $this->registry->getType($name));
    }

    public function testGetTypeWithNullName()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "string", "NULL" given.');

        $this->registry->getType(null);
    }

    public function testGetTypeWithEmptyName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not load a block type "".');

        $this->registry->getType('');
    }

    public function testGetTypeWithNotStringName()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "string", "integer" given.');

        $this->registry->getType(1);
    }

    public function testGetUndefinedType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not load a block type "widget".');

        $this->extension->expects($this->once())
            ->method('hasType')
            ->with('widget')
            ->willReturn(false);
        $this->extension->expects($this->never())
            ->method('getType');

        $this->registry->getType('widget');
    }

    public function testGetTypeExtensions()
    {
        $name = 'test';
        $typeExtension = $this->createMock(BlockTypeExtensionInterface::class);

        $this->extension->expects($this->once())
            ->method('hasTypeExtensions')
            ->with($name)
            ->willReturn(true);
        $this->extension->expects($this->once())
            ->method('getTypeExtensions')
            ->with($name)
            ->willReturn([$typeExtension]);

        $result = $this->registry->getTypeExtensions($name);
        $this->assertCount(1, $result);
        $this->assertSame($typeExtension, $result[0]);
    }

    public function testGetContextConfigurators()
    {
        $configurator = $this->createMock(ContextConfiguratorInterface::class);

        $this->extension->expects($this->once())
            ->method('hasContextConfigurators')
            ->willReturn(true);
        $this->extension->expects($this->once())
            ->method('getContextConfigurators')
            ->willReturn([$configurator]);

        $result = $this->registry->getContextConfigurators();
        $this->assertCount(1, $result);
        $this->assertSame($configurator, $result[0]);
    }

    public function testFindDataProvider()
    {
        $name = 'test';
        $dataProvider = $this->createMock(\stdClass::class);

        $this->extension->expects($this->once())
            ->method('hasDataProvider')
            ->with($name)
            ->willReturn(true);
        $this->extension->expects($this->once())
            ->method('getDataProvider')
            ->with($name)
            ->willReturn($dataProvider);

        $this->assertSame($dataProvider, $this->registry->findDataProvider($name));
        // check that the loaded data provider is cached
        $this->assertSame($dataProvider, $this->registry->findDataProvider($name));
    }

    public function testFindDataProviderWithNullName()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "string", "NULL" given.');

        $this->registry->findDataProvider(null);
    }

    public function testFindDataProviderWithEmptyName()
    {
        $this->assertNull($this->registry->findDataProvider(''));
    }

    public function testFindDataProviderWithNotStringName()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "string", "integer" given.');

        $this->registry->findDataProvider(1);
    }

    public function testFindUndefinedDataProvider()
    {
        $this->extension->expects($this->once())
            ->method('hasDataProvider')
            ->with('foo')
            ->willReturn(false);
        $this->extension->expects($this->never())
            ->method('getDataProvider');

        $this->assertNull($this->registry->findDataProvider('foo'));
    }

    public function testConfigureOptions()
    {
        $name = 'test';
        $resolver = $this->createMock(OptionsResolver::class);

        $typeExtension = $this->createMock(BlockTypeExtensionInterface::class);

        $this->extension->expects($this->once())
            ->method('hasTypeExtensions')
            ->with($name)
            ->willReturn(true);
        $this->extension->expects($this->once())
            ->method('getTypeExtensions')
            ->with($name)
            ->willReturn([$typeExtension]);
        $typeExtension->expects($this->once())
            ->method('configureOptions')
            ->with($this->identicalTo($resolver));

        $this->registry->configureOptions($name, $resolver);
    }

    public function testBuildBlock()
    {
        $name = 'test';
        $builder = $this->createMock(BlockBuilderInterface::class);
        $options = new Options(['foo' => 'bar']);

        $typeExtension = $this->createMock(BlockTypeExtensionInterface::class);

        $this->extension->expects($this->once())
            ->method('hasTypeExtensions')
            ->with($name)
            ->willReturn(true);
        $this->extension->expects($this->once())
            ->method('getTypeExtensions')
            ->with($name)
            ->willReturn([$typeExtension]);
        $typeExtension->expects($this->once())
            ->method('buildBlock')
            ->with($this->identicalTo($builder), $options);

        $this->registry->buildBlock($name, $builder, $options);
    }

    public function testBuildView()
    {
        $name = 'test';
        $view = new BlockView();
        $block = $this->createMock(BlockInterface::class);
        $options = new Options(['foo' => 'bar']);
        $typeExtension = $this->createMock(BlockTypeExtensionInterface::class);

        $this->extension->expects($this->once())
            ->method('hasTypeExtensions')
            ->with($name)
            ->willReturn(true);
        $this->extension->expects($this->once())
            ->method('getTypeExtensions')
            ->with($name)
            ->willReturn([$typeExtension]);
        $typeExtension->expects($this->once())
            ->method('buildView')
            ->with($this->identicalTo($view), $this->identicalTo($block), $options);

        $this->registry->buildView($name, $view, $block, $options);
    }

    public function testFinishView()
    {
        $name = 'test';
        $view = new BlockView();
        $block = $this->createMock(BlockInterface::class);

        $typeExtension = $this->createMock(BlockTypeExtensionInterface::class);

        $this->extension->expects($this->once())
            ->method('hasTypeExtensions')
            ->with($name)
            ->willReturn(true);
        $this->extension->expects($this->once())
            ->method('getTypeExtensions')
            ->with($name)
            ->willReturn([$typeExtension]);
        $typeExtension->expects($this->once())
            ->method('finishView')
            ->with($this->identicalTo($view), $this->identicalTo($block));

        $this->registry->finishView($name, $view, $block);
    }

    public function testUpdateLayout()
    {
        $id = 'test';
        $layoutManipulator = $this->createMock(LayoutManipulatorInterface::class);
        $item = $this->createMock(LayoutItemInterface::class);
        $item->expects($this->once())
            ->method('getContext')
            ->willReturn($this->createMock(ContextInterface::class));

        $layoutUpdate = $this->createMock(LayoutUpdateInterface::class);

        $this->extension->expects($this->once())
            ->method('hasLayoutUpdates')
            ->with($item)
            ->willReturn(true);
        $this->extension->expects($this->once())
            ->method('getLayoutUpdates')
            ->with($item)
            ->willReturn([$layoutUpdate]);
        $layoutUpdate->expects($this->once())
            ->method('updateLayout')
            ->with($this->identicalTo($layoutManipulator), $this->identicalTo($item));

        $this->registry->updateLayout($id, $layoutManipulator, $item);
    }

    public function testConfigureContext()
    {
        $context = $this->createMock(ContextInterface::class);

        $contextConfigurator = $this->createMock(ContextConfiguratorInterface::class);

        $this->extension->expects($this->once())
            ->method('hasContextConfigurators')
            ->willReturn(true);
        $this->extension->expects($this->once())
            ->method('getContextConfigurators')
            ->willReturn([$contextConfigurator]);
        $contextConfigurator->expects($this->once())
            ->method('configureContext')
            ->with($this->identicalTo($context));

        $this->registry->configureContext($context);
    }
}
