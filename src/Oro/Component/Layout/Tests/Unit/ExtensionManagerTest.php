<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Extension\Core\CoreExtension;
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
        $this->extensionManager->addExtension(new CoreExtension());
        $this->extension = $this->getMock('Oro\Component\Layout\ExtensionInterface');
        $this->extensionManager->addExtension($this->extension);
    }

    public function testGetTypeFromCoreExtension()
    {
        $this->extension->expects($this->never())
            ->method('hasType')
            ->with(BaseType::NAME);
        $this->extension->expects($this->never())
            ->method('getType')
            ->with(BaseType::NAME);

        $type = $this->extensionManager->getType(BaseType::NAME);
        $this->assertInstanceOf('Oro\Component\Layout\Block\Type\BaseType', $type);
    }

    public function testGetType()
    {
        $name = 'test';
        $type = $this->getMock('Oro\Component\Layout\BlockTypeInterface');

        $this->extension->expects($this->once())
            ->method('hasType')
            ->with($name)
            ->will($this->returnValue(true));
        $this->extension->expects($this->once())
            ->method('getType')
            ->with($name)
            ->will($this->returnValue($type));

        $this->assertSame($type, $this->extensionManager->getType($name));
        // check that the created block type is cached
        $this->assertSame($type, $this->extensionManager->getType($name));
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage Expected argument of type "string", "NULL" given.
     */
    public function testGetTypeWithNullName()
    {
        $this->extensionManager->getType(null);
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Could not load block type "".
     */
    public function testGetTypeWithEmptyName()
    {
        $this->extensionManager->getType('');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage Expected argument of type "string", "integer" given.
     */
    public function testGetTypeWithNotStringName()
    {
        $this->extensionManager->getType(1);
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Could not load block type "widget".
     */
    public function testGetUndefinedBlockType()
    {
        $this->extension->expects($this->once())
            ->method('hasType')
            ->with('widget')
            ->will($this->returnValue(false));
        $this->extension->expects($this->never())
            ->method('getType');

        $this->extensionManager->getType('widget');
    }

    public function testSetDefaultOptions()
    {
        $name     = 'test';
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');

        $typeExtension = $this->getMock('Oro\Component\Layout\BlockTypeExtensionInterface');

        $this->extension->expects($this->once())
            ->method('hasTypeExtensions')
            ->with($name)
            ->will($this->returnValue(true));
        $this->extension->expects($this->once())
            ->method('getTypeExtensions')
            ->with($name)
            ->will($this->returnValue([$typeExtension]));
        $typeExtension->expects($this->once())
            ->method('setDefaultOptions')
            ->with($this->identicalTo($resolver));

        $this->extensionManager->setDefaultOptions($name, $resolver);
    }

    public function testBuildBlock()
    {
        $name    = 'test';
        $builder = $this->getMock('Oro\Component\Layout\BlockBuilderInterface');
        $options = ['foo' => 'bar'];

        $typeExtension = $this->getMock('Oro\Component\Layout\BlockTypeExtensionInterface');

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

        $this->extensionManager->buildBlock($name, $builder, $options);
    }

    public function testBuildView()
    {
        $name    = 'test';
        $view    = new BlockView();
        $block   = $this->getMock('Oro\Component\Layout\BlockInterface');
        $options = ['foo' => 'bar'];

        $typeExtension = $this->getMock('Oro\Component\Layout\BlockTypeExtensionInterface');

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

        $this->extensionManager->buildView($name, $view, $block, $options);
    }

    public function testFinishView()
    {
        $name    = 'test';
        $view    = new BlockView();
        $block   = $this->getMock('Oro\Component\Layout\BlockInterface');
        $options = ['foo' => 'bar'];

        $typeExtension = $this->getMock('Oro\Component\Layout\BlockTypeExtensionInterface');

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
            ->with($this->identicalTo($view), $this->identicalTo($block), $options);

        $this->extensionManager->finishView($name, $view, $block, $options);
    }

    public function testUpdateLayout()
    {
        $id                = 'test';
        $layoutManipulator = $this->getMock('Oro\Component\Layout\LayoutManipulatorInterface');
        $item              = $this->getMock('Oro\Component\Layout\LayoutItemInterface');

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
            ->with($this->identicalTo($layoutManipulator), $this->identicalTo($item));

        $this->extensionManager->updateLayout($id, $layoutManipulator, $item);
    }
}
