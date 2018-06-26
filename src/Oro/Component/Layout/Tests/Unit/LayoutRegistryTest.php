<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Extension\Core\CoreExtension;
use Oro\Component\Layout\LayoutRegistry;

class LayoutRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var LayoutRegistry */
    protected $registry;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $extension;

    protected function setUp()
    {
        $this->registry = new LayoutRegistry();
        $this->registry->addExtension(new CoreExtension());
        $this->extension = $this->createMock('Oro\Component\Layout\Extension\ExtensionInterface');
        $this->registry->addExtension($this->extension);
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
        $this->assertInstanceOf('Oro\Component\Layout\Block\Type\BaseType', $type);
    }

    public function testGetType()
    {
        $name = 'test';
        $type = $this->createMock('Oro\Component\Layout\BlockTypeInterface');

        $this->extension->expects($this->once())
            ->method('hasType')
            ->with($name)
            ->will($this->returnValue(true));
        $this->extension->expects($this->once())
            ->method('getType')
            ->with($name)
            ->will($this->returnValue($type));

        $this->assertSame($type, $this->registry->getType($name));
        // check that the loaded block type is cached
        $this->assertSame($type, $this->registry->getType($name));
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "string", "NULL" given.
     */
    public function testGetTypeWithNullName()
    {
        $this->registry->getType(null);
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage Could not load a block type "".
     */
    public function testGetTypeWithEmptyName()
    {
        $this->registry->getType('');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "string", "integer" given.
     */
    public function testGetTypeWithNotStringName()
    {
        $this->registry->getType(1);
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage Could not load a block type "widget".
     */
    public function testGetUndefinedType()
    {
        $this->extension->expects($this->once())
            ->method('hasType')
            ->with('widget')
            ->will($this->returnValue(false));
        $this->extension->expects($this->never())
            ->method('getType');

        $this->registry->getType('widget');
    }

    public function testGetTypeExtensions()
    {
        $name          = 'test';
        $typeExtension = $this->createMock('Oro\Component\Layout\BlockTypeExtensionInterface');

        $this->extension->expects($this->once())
            ->method('hasTypeExtensions')
            ->with($name)
            ->will($this->returnValue(true));
        $this->extension->expects($this->once())
            ->method('getTypeExtensions')
            ->with($name)
            ->will($this->returnValue([$typeExtension]));

        $result = $this->registry->getTypeExtensions($name);
        $this->assertCount(1, $result);
        $this->assertSame($typeExtension, $result[0]);
    }

    public function testGetContextConfigurators()
    {
        $configurator = $this->createMock('Oro\Component\Layout\ContextConfiguratorInterface');

        $this->extension->expects($this->once())
            ->method('hasContextConfigurators')
            ->will($this->returnValue(true));
        $this->extension->expects($this->once())
            ->method('getContextConfigurators')
            ->will($this->returnValue([$configurator]));

        $result = $this->registry->getContextConfigurators();
        $this->assertCount(1, $result);
        $this->assertSame($configurator, $result[0]);
    }

    public function testFindDataProvider()
    {
        $name         = 'test';
        $dataProvider = $this->createMock(\stdClass::class);

        $this->extension->expects($this->once())
            ->method('hasDataProvider')
            ->with($name)
            ->will($this->returnValue(true));
        $this->extension->expects($this->once())
            ->method('getDataProvider')
            ->with($name)
            ->will($this->returnValue($dataProvider));

        $this->assertSame($dataProvider, $this->registry->findDataProvider($name));
        // check that the loaded data provider is cached
        $this->assertSame($dataProvider, $this->registry->findDataProvider($name));
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "string", "NULL" given.
     */
    public function testFindDataProviderWithNullName()
    {
        $this->registry->findDataProvider(null);
    }

    public function testFindDataProviderWithEmptyName()
    {
        $this->assertNull($this->registry->findDataProvider(''));
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "string", "integer" given.
     */
    public function testFindDataProviderWithNotStringName()
    {
        $this->registry->findDataProvider(1);
    }

    public function testFindUndefinedDataProvider()
    {
        $this->extension->expects($this->once())
            ->method('hasDataProvider')
            ->with('foo')
            ->will($this->returnValue(false));
        $this->extension->expects($this->never())
            ->method('getDataProvider');

        $this->assertNull($this->registry->findDataProvider('foo'));
    }

    public function testConfigureOptions()
    {
        $name     = 'test';
        /** @var OptionsResolver $resolver */
        $resolver = $this->createMock('Oro\Component\Layout\Block\OptionsResolver\OptionsResolver');

        $typeExtension = $this->createMock('Oro\Component\Layout\BlockTypeExtensionInterface');

        $this->extension->expects($this->once())
            ->method('hasTypeExtensions')
            ->with($name)
            ->will($this->returnValue(true));
        $this->extension->expects($this->once())
            ->method('getTypeExtensions')
            ->with($name)
            ->will($this->returnValue([$typeExtension]));
        $typeExtension->expects($this->once())
            ->method('configureOptions')
            ->with($this->identicalTo($resolver));

        $this->registry->configureOptions($name, $resolver);
    }

    public function testBuildBlock()
    {
        $name    = 'test';
        $builder = $this->createMock('Oro\Component\Layout\BlockBuilderInterface');
        $options = new Options(['foo' => 'bar']);

        $typeExtension = $this->createMock('Oro\Component\Layout\BlockTypeExtensionInterface');

        $this->extension->expects($this->once())
            ->method('hasTypeExtensions')
            ->with($name)
            ->will($this->returnValue(true));
        $this->extension->expects($this->once())
            ->method('getTypeExtensions')
            ->with($name)
            ->will($this->returnValue([$typeExtension]));
        $typeExtension->expects($this->once())
            ->method('buildBlock')
            ->with($this->identicalTo($builder), $options);

        $this->registry->buildBlock($name, $builder, $options);
    }

    public function testBuildView()
    {
        $name    = 'test';
        $view    = new BlockView();
        $block   = $this->createMock('Oro\Component\Layout\BlockInterface');
        $options = new Options(['foo' => 'bar']);
        $typeExtension = $this->createMock('Oro\Component\Layout\BlockTypeExtensionInterface');

        $this->extension->expects($this->once())
            ->method('hasTypeExtensions')
            ->with($name)
            ->will($this->returnValue(true));
        $this->extension->expects($this->once())
            ->method('getTypeExtensions')
            ->with($name)
            ->will($this->returnValue([$typeExtension]));
        $typeExtension->expects($this->once())
            ->method('buildView')
            ->with($this->identicalTo($view), $this->identicalTo($block), $options);

        $this->registry->buildView($name, $view, $block, $options);
    }

    public function testFinishView()
    {
        $name    = 'test';
        $view    = new BlockView();
        $block   = $this->createMock('Oro\Component\Layout\BlockInterface');

        $typeExtension = $this->createMock('Oro\Component\Layout\BlockTypeExtensionInterface');

        $this->extension->expects($this->once())
            ->method('hasTypeExtensions')
            ->with($name)
            ->will($this->returnValue(true));
        $this->extension->expects($this->once())
            ->method('getTypeExtensions')
            ->with($name)
            ->will($this->returnValue([$typeExtension]));
        $typeExtension->expects($this->once())
            ->method('finishView')
            ->with($this->identicalTo($view), $this->identicalTo($block));

        $this->registry->finishView($name, $view, $block);
    }

    public function testUpdateLayout()
    {
        $id                = 'test';
        $layoutManipulator = $this->createMock('Oro\Component\Layout\LayoutManipulatorInterface');
        $item              = $this->createMock('Oro\Component\Layout\LayoutItemInterface');
        $item->expects($this->once())->method('getContext')
            ->willReturn($this->createMock('Oro\Component\Layout\ContextInterface'));

        $layoutUpdate = $this->createMock('Oro\Component\Layout\LayoutUpdateInterface');

        $this->extension->expects($this->once())
            ->method('hasLayoutUpdates')
            ->with($item)
            ->will($this->returnValue(true));
        $this->extension->expects($this->once())
            ->method('getLayoutUpdates')
            ->with($item)
            ->will($this->returnValue([$layoutUpdate]));
        $layoutUpdate->expects($this->once())
            ->method('updateLayout')
            ->with($this->identicalTo($layoutManipulator), $this->identicalTo($item));

        $this->registry->updateLayout($id, $layoutManipulator, $item);
    }

    public function testConfigureContext()
    {
        $context = $this->createMock('Oro\Component\Layout\ContextInterface');

        $contextConfigurator = $this->createMock('Oro\Component\Layout\ContextConfiguratorInterface');

        $this->extension->expects($this->once())
            ->method('hasContextConfigurators')
            ->will($this->returnValue(true));
        $this->extension->expects($this->once())
            ->method('getContextConfigurators')
            ->will($this->returnValue([$contextConfigurator]));
        $contextConfigurator->expects($this->once())
            ->method('configureContext')
            ->with($this->identicalTo($context));

        $this->registry->configureContext($context);
    }
}
