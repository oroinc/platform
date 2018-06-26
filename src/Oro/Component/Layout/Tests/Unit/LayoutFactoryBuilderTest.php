<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockBuilderInterface;
use Oro\Component\Layout\BlockTypeExtensionInterface;
use Oro\Component\Layout\BlockTypeInterface;
use Oro\Component\Layout\BlockViewCache;
use Oro\Component\Layout\DeferredLayoutManipulatorInterface;
use Oro\Component\Layout\ExpressionLanguage\ExpressionProcessor;
use Oro\Component\Layout\Extension\ExtensionInterface;
use Oro\Component\Layout\LayoutFactory;
use Oro\Component\Layout\LayoutFactoryBuilder;
use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\LayoutRegistry;
use Oro\Component\Layout\LayoutRendererInterface;
use Oro\Component\Layout\LayoutRendererRegistry;
use Oro\Component\Layout\LayoutUpdateInterface;

class LayoutFactoryBuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExpressionProcessor|\PHPUnit\Framework\MockObject\MockObject */
    protected $expressionProcessor;

    /** @var BlockViewCache|\PHPUnit\Framework\MockObject\MockObject */
    protected $blockViewCache;

    /** @var LayoutFactoryBuilder */
    protected $layoutFactoryBuilder;

    protected function setUp()
    {
        $this->expressionProcessor = $this
            ->getMockBuilder('Oro\Component\Layout\ExpressionLanguage\ExpressionProcessor')
            ->disableOriginalConstructor()
            ->getMock();
        $this->blockViewCache = $this
            ->getMockBuilder('Oro\Component\Layout\BlockViewCache')
            ->disableOriginalConstructor()
            ->getMock();

        $this->layoutFactoryBuilder = new LayoutFactoryBuilder($this->expressionProcessor, $this->blockViewCache);
    }

    public function testGetEmptyLayoutFactory()
    {
        $layoutFactory = $this->layoutFactoryBuilder->getLayoutFactory();
        $this->assertInstanceOf('Oro\Component\Layout\LayoutFactoryInterface', $layoutFactory);
    }

    public function testGetLayoutFactoryWithImplicitSetOfDefaultRenderer()
    {
        /** @var LayoutRendererInterface $renderer1 */
        $renderer1 = $this->createMock('Oro\Component\Layout\LayoutRendererInterface');
        /** @var LayoutRendererInterface $renderer2 */
        $renderer2 = $this->createMock('Oro\Component\Layout\LayoutRendererInterface');
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
        $renderer1 = $this->createMock('Oro\Component\Layout\LayoutRendererInterface');
        /** @var LayoutRendererInterface $renderer2 */
        $renderer2 = $this->createMock('Oro\Component\Layout\LayoutRendererInterface');
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

    public function testGetLayoutFactoryWithDebug()
    {
        $this->layoutFactoryBuilder->setDebug(true);
        $layoutFactory = $this->layoutFactoryBuilder->getLayoutFactory();

        $registry = new LayoutRegistry();
        $rendererRegistry = new LayoutRendererRegistry();
        $this->assertEquals(
            new LayoutFactory($registry, $rendererRegistry, $this->expressionProcessor),
            $layoutFactory
        );
    }

    public function testGetLayoutFactoryWithoutDebug()
    {
        $layoutFactory = $this->layoutFactoryBuilder->getLayoutFactory();

        $registry = new LayoutRegistry();
        $rendererRegistry = new LayoutRendererRegistry();
        $this->assertEquals(
            new LayoutFactory($registry, $rendererRegistry, $this->expressionProcessor, $this->blockViewCache),
            $layoutFactory
        );
    }

    public function testAddExtension()
    {
        $name = 'test';
        $type = $this->createMock('Oro\Component\Layout\BlockTypeInterface');

        /** @var ExtensionInterface|\PHPUnit\Framework\MockObject\MockObject $extension */
        $extension     = $this->createMock('Oro\Component\Layout\Extension\ExtensionInterface');
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
        /** @var BlockTypeInterface|\PHPUnit\Framework\MockObject\MockObject $type */
        $type = $this->createMock('Oro\Component\Layout\BlockTypeInterface');
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
        /** @var BlockTypeExtensionInterface|\PHPUnit\Framework\MockObject\MockObject $typeExtension */
        $typeExtension = $this->createMock('Oro\Component\Layout\BlockTypeExtensionInterface');
        $typeExtension->expects($this->once())
            ->method('getExtendedType')
            ->will($this->returnValue($name));
        /** @var BlockBuilderInterface $blockBuilder */
        $blockBuilder = $this->createMock('Oro\Component\Layout\BlockBuilderInterface');

        $layoutFactory = $this->layoutFactoryBuilder
            ->addTypeExtension($typeExtension)
            ->getLayoutFactory();

        $typeExtension->expects($this->once())
            ->method('buildBlock')
            ->with($this->identicalTo($blockBuilder), new Options());

        $layoutFactory->getRegistry()->buildBlock($name, $blockBuilder, new Options());
    }

    public function testAddLayoutUpdate()
    {
        $id                = 'test';
        /** @var LayoutUpdateInterface|\PHPUnit\Framework\MockObject\MockObject $layoutUpdate */
        $layoutUpdate      = $this->createMock('Oro\Component\Layout\LayoutUpdateInterface');
        /** @var DeferredLayoutManipulatorInterface $layoutManipulator */
        $layoutManipulator = $this->createMock('Oro\Component\Layout\DeferredLayoutManipulatorInterface');
        /** @var LayoutItemInterface|\PHPUnit\Framework\MockObject\MockObject $layoutItem */
        $layoutItem        = $this->createMock('Oro\Component\Layout\LayoutItemInterface');
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
