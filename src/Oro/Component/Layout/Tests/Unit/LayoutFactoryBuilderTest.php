<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockBuilderInterface;
use Oro\Component\Layout\BlockTypeExtensionInterface;
use Oro\Component\Layout\BlockTypeInterface;
use Oro\Component\Layout\Extension\ExtensionInterface;
use Oro\Component\Layout\LayoutFactoryBuilder;
use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\LayoutRendererInterface;
use Oro\Component\Layout\LayoutUpdateInterface;
use Oro\Component\Layout\DeferredLayoutManipulatorInterface;

class LayoutFactoryBuilderTest extends \PHPUnit_Framework_TestCase
{
    /** @var LayoutFactoryBuilder */
    protected $layoutFactoryBuilder;

    protected function setUp()
    {
        $this->layoutFactoryBuilder = new LayoutFactoryBuilder();
    }

    public function testGetEmptyLayoutFactory()
    {
        $layoutFactory = $this->layoutFactoryBuilder->getLayoutFactory();
        $this->assertInstanceOf('Oro\Component\Layout\LayoutFactoryInterface', $layoutFactory);
    }

    public function testGetLayoutFactoryWithImplicitSetOfDefaultRenderer()
    {
        /** @var LayoutRendererInterface $renderer1 */
        $renderer1 = $this->getMock('Oro\Component\Layout\LayoutRendererInterface');
        /** @var LayoutRendererInterface $renderer2 */
        $renderer2 = $this->getMock('Oro\Component\Layout\LayoutRendererInterface');
        $this->layoutFactoryBuilder
            ->addRenderer('renderer1', $renderer1)
            ->addRenderer('renderer2', $renderer2);

        $layoutFactory = $this->layoutFactoryBuilder->getLayoutFactory();
        $this->assertSame(
            $renderer1,
            $layoutFactory->getRendererRegistry()->getRenderer('renderer1')
        );
        $this->assertSame(
            $renderer2,
            $layoutFactory->getRendererRegistry()->getRenderer('renderer2')
        );
        // check default renderer
        $this->assertSame(
            $renderer1,
            $layoutFactory->getRendererRegistry()->getRenderer()
        );
    }

    public function testGetLayoutFactoryWithExplicitSetOfDefaultRenderer()
    {
        /** @var LayoutRendererInterface $renderer1 */
        $renderer1 = $this->getMock('Oro\Component\Layout\LayoutRendererInterface');
        /** @var LayoutRendererInterface $renderer2 */
        $renderer2 = $this->getMock('Oro\Component\Layout\LayoutRendererInterface');
        $this->layoutFactoryBuilder
            ->addRenderer('renderer1', $renderer1)
            ->addRenderer('renderer2', $renderer2)
            ->setDefaultRenderer('renderer2');

        $layoutFactory = $this->layoutFactoryBuilder->getLayoutFactory();
        $this->assertSame(
            $renderer1,
            $layoutFactory->getRendererRegistry()->getRenderer('renderer1')
        );
        $this->assertSame(
            $renderer2,
            $layoutFactory->getRendererRegistry()->getRenderer('renderer2')
        );
        // check default renderer
        $this->assertSame(
            $renderer2,
            $layoutFactory->getRendererRegistry()->getRenderer()
        );
    }

    public function testAddExtension()
    {
        $name = 'test';
        $type = $this->getMock('Oro\Component\Layout\BlockTypeInterface');

        /** @var ExtensionInterface|\PHPUnit_Framework_MockObject_MockObject $extension */
        $extension     = $this->getMock('Oro\Component\Layout\Extension\ExtensionInterface');
        $layoutFactory = $this->layoutFactoryBuilder
            ->addExtension($extension)
            ->getLayoutFactory();

        $extension->expects($this->once())
            ->method('hasType')
            ->with($name)
            ->will($this->returnValue(true));
        $extension->expects($this->once())
            ->method('getType')
            ->with($name)
            ->will($this->returnValue($type));

        $this->assertSame(
            $type,
            $layoutFactory->getRegistry()->getType($name)
        );
    }

    public function testAddType()
    {
        $name = 'test';
        /** @var BlockTypeInterface|\PHPUnit_Framework_MockObject_MockObject $type */
        $type = $this->getMock('Oro\Component\Layout\BlockTypeInterface');
        $type->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($name));

        $layoutFactory = $this->layoutFactoryBuilder
            ->addType($type)
            ->getLayoutFactory();

        $this->assertSame(
            $type,
            $layoutFactory->getRegistry()->getType($name)
        );
    }

    public function testAddTypeExtension()
    {
        $name          = 'test';
        /** @var BlockTypeExtensionInterface|\PHPUnit_Framework_MockObject_MockObject $typeExtension */
        $typeExtension = $this->getMock('Oro\Component\Layout\BlockTypeExtensionInterface');
        $typeExtension->expects($this->once())
            ->method('getExtendedType')
            ->will($this->returnValue($name));
        /** @var BlockBuilderInterface $blockBuilder */
        $blockBuilder = $this->getMock('Oro\Component\Layout\BlockBuilderInterface');

        $layoutFactory = $this->layoutFactoryBuilder
            ->addTypeExtension($typeExtension)
            ->getLayoutFactory();

        $typeExtension->expects($this->once())
            ->method('buildBlock')
            ->with($this->identicalTo($blockBuilder), []);

        $options = [];
        $layoutFactory->getRegistry()->buildBlock($name, $blockBuilder, $options);
    }

    public function testAddLayoutUpdate()
    {
        $id                = 'test';
        /** @var LayoutUpdateInterface|\PHPUnit_Framework_MockObject_MockObject $layoutUpdate */
        $layoutUpdate      = $this->getMock('Oro\Component\Layout\LayoutUpdateInterface');
        /** @var DeferredLayoutManipulatorInterface $layoutManipulator */
        $layoutManipulator = $this->getMock('Oro\Component\Layout\DeferredLayoutManipulatorInterface');
        /** @var LayoutItemInterface|\PHPUnit_Framework_MockObject_MockObject $layoutItem */
        $layoutItem        = $this->getMock('Oro\Component\Layout\LayoutItemInterface');
        $layoutItem->expects($this->any())->method('getId')->willReturn($id);

        $layoutFactory = $this->layoutFactoryBuilder
            ->addLayoutUpdate($id, $layoutUpdate)
            ->getLayoutFactory();

        $layoutUpdate->expects($this->once())
            ->method('updateLayout')
            ->with($this->identicalTo($layoutManipulator), $this->identicalTo($layoutItem));

        $layoutFactory->getRegistry()->updateLayout($id, $layoutManipulator, $layoutItem);
    }
}
