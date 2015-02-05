<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\ExtensionManager;

class ExtensionManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ExtensionManager */
    protected $extensionManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $extension;

    protected function setUp()
    {
        $this->extensionManager = new ExtensionManager();

        $this->extension = $this->getMock('Oro\Component\Layout\ExtensionInterface');
        $this->extensionManager->addExtension($this->extension);
    }

    public function testGetBlockTypeFromCoreExtension()
    {
        $this->extension->expects($this->never())
            ->method('hasBlockType')
            ->with(BaseType::NAME);
        $this->extension->expects($this->never())
            ->method('getBlockType')
            ->with(BaseType::NAME);

        $blockType = $this->extensionManager->getBlockType(BaseType::NAME);
        $this->assertInstanceOf('Oro\Component\Layout\Block\Type\BaseType', $blockType);
    }

    public function testGetBlockType()
    {
        $name      = 'test';
        $blockType = $this->getMock('Oro\Component\Layout\BlockTypeInterface');

        $blockType->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($name));
        $this->extension->expects($this->once())
            ->method('hasBlockType')
            ->with($name)
            ->will($this->returnValue(true));
        $this->extension->expects($this->once())
            ->method('getBlockType')
            ->with($name)
            ->will($this->returnValue($blockType));

        $this->assertSame($blockType, $this->extensionManager->getBlockType($name));
        // check that the created block type is cached
        $this->assertSame($blockType, $this->extensionManager->getBlockType($name));
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The block type name must not be empty.
     */
    public function testGetBlockTypeWithEmptyName($name)
    {
        $this->extensionManager->getBlockType($name);
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage Expected argument of type "string", "integer" given.
     */
    public function testGetBlockTypeWithNotStringName()
    {
        $this->extensionManager->getBlockType(1);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage The block type name does not match the name declared in the class implementing this type. Expected "widget", given "button".
     */
    // @codingStandardsIgnoreEnd
    public function testGetBlockTypeWhenGivenNameDoesNotMatchNameDeclaredInClass()
    {
        $blockType = $this->getMock('Oro\Component\Layout\BlockTypeInterface');

        $this->extension->expects($this->once())
            ->method('hasBlockType')
            ->with('widget')
            ->will($this->returnValue(true));
        $this->extension->expects($this->once())
            ->method('getBlockType')
            ->with('widget')
            ->will($this->returnValue($blockType));
        $blockType->expects($this->exactly(2))
            ->method('getName')
            ->will($this->returnValue('button'));

        $this->extensionManager->getBlockType('widget');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage The block type named "widget" was not found.
     */
    public function testGetUndefinedBlockType()
    {
        $this->extension->expects($this->once())
            ->method('hasBlockType')
            ->with('widget')
            ->will($this->returnValue(false));
        $this->extension->expects($this->never())
            ->method('getBlockType');

        $this->extensionManager->getBlockType('widget');
    }

    public function testSetDefaultOptions()
    {
        $name     = 'test';
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');

        $blockTypeExtension = $this->getMock('Oro\Component\Layout\BlockTypeExtensionInterface');

        $this->extension->expects($this->once())
            ->method('hasBlockTypeExtensions')
            ->with($name)
            ->will($this->returnValue(true));
        $this->extension->expects($this->once())
            ->method('getBlockTypeExtensions')
            ->with($name)
            ->will($this->returnValue([$blockTypeExtension]));
        $blockTypeExtension->expects($this->once())
            ->method('setDefaultOptions')
            ->with($this->identicalTo($resolver));

        $this->extensionManager->setDefaultOptions($name, $resolver);
    }

    public function testBuildBlock()
    {
        $name    = 'test';
        $builder = $this->getMock('Oro\Component\Layout\BlockBuilderInterface');
        $options = ['foo' => 'bar'];

        $blockTypeExtension = $this->getMock('Oro\Component\Layout\BlockTypeExtensionInterface');

        $this->extension->expects($this->once())
            ->method('hasBlockTypeExtensions')
            ->with($name)
            ->will($this->returnValue(true));
        $this->extension->expects($this->once())
            ->method('getBlockTypeExtensions')
            ->with($name)
            ->will($this->returnValue([$blockTypeExtension]));
        $blockTypeExtension->expects($this->once())
            ->method('buildBlock')
            ->with($this->identicalTo($builder), $options);

        $this->extensionManager->buildBlock($name, $builder, $options);
    }

    public function testBuildView()
    {
        $name    = 'test';
        $view    = new BlockView();
        $block   = $this->getMock('Oro\Component\Layout\BlockInterface');
        $options = ['foo' => 'bar'];

        $blockTypeExtension = $this->getMock('Oro\Component\Layout\BlockTypeExtensionInterface');

        $this->extension->expects($this->once())
            ->method('hasBlockTypeExtensions')
            ->with($name)
            ->will($this->returnValue(true));
        $this->extension->expects($this->once())
            ->method('getBlockTypeExtensions')
            ->with($name)
            ->will($this->returnValue([$blockTypeExtension]));
        $blockTypeExtension->expects($this->once())
            ->method('buildView')
            ->with($this->identicalTo($view), $this->identicalTo($block), $options);

        $this->extensionManager->buildView($name, $view, $block, $options);
    }

    public function testFinishView()
    {
        $name    = 'test';
        $view    = new BlockView();
        $block   = $this->getMock('Oro\Component\Layout\BlockInterface');
        $options = ['foo' => 'bar'];

        $blockTypeExtension = $this->getMock('Oro\Component\Layout\BlockTypeExtensionInterface');

        $this->extension->expects($this->once())
            ->method('hasBlockTypeExtensions')
            ->with($name)
            ->will($this->returnValue(true));
        $this->extension->expects($this->once())
            ->method('getBlockTypeExtensions')
            ->with($name)
            ->will($this->returnValue([$blockTypeExtension]));
        $blockTypeExtension->expects($this->once())
            ->method('finishView')
            ->with($this->identicalTo($view), $this->identicalTo($block), $options);

        $this->extensionManager->finishView($name, $view, $block, $options);
    }

    public function testUpdateLayout()
    {
        $id                = 'test';
        $layoutManipulator = $this->getMock('Oro\Component\Layout\LayoutManipulatorInterface');

        $layoutUpdate = $this->getMock('Oro\Component\Layout\LayoutUpdateInterface');

        $this->extension->expects($this->once())
            ->method('hasLayoutUpdates')
            ->with($id)
            ->will($this->returnValue(true));
        $this->extension->expects($this->once())
            ->method('getLayoutUpdates')
            ->with($id)
            ->will($this->returnValue([$layoutUpdate]));
        $layoutUpdate->expects($this->once())
            ->method('updateLayout')
            ->with($this->identicalTo($layoutManipulator));

        $this->extensionManager->updateLayout($id, $layoutManipulator);
    }

    public function emptyStringDataProvider()
    {
        return [
            [null],
            ['']
        ];
    }
}
