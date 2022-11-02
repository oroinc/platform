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
use Oro\Component\Layout\LayoutFactoryInterface;
use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\LayoutRegistry;
use Oro\Component\Layout\LayoutRendererInterface;
use Oro\Component\Layout\LayoutRendererRegistry;
use Oro\Component\Layout\LayoutUpdateInterface;

class LayoutFactoryBuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExpressionProcessor|\PHPUnit\Framework\MockObject\MockObject */
    private $expressionProcessor;

    /** @var BlockViewCache|\PHPUnit\Framework\MockObject\MockObject */
    private $blockViewCache;

    /** @var LayoutFactoryBuilder */
    private $layoutFactoryBuilder;

    protected function setUp(): void
    {
        $this->expressionProcessor = $this->createMock(ExpressionProcessor::class);
        $this->blockViewCache = $this->createMock(BlockViewCache::class);

        $this->layoutFactoryBuilder = new LayoutFactoryBuilder($this->expressionProcessor, $this->blockViewCache);
    }

    public function testGetEmptyLayoutFactory()
    {
        $layoutFactory = $this->layoutFactoryBuilder->getLayoutFactory();
        $this->assertInstanceOf(LayoutFactoryInterface::class, $layoutFactory);
    }

    public function testGetLayoutFactoryWithImplicitSetOfDefaultRenderer()
    {
        $renderer1 = $this->createMock(LayoutRendererInterface::class);
        $renderer2 = $this->createMock(LayoutRendererInterface::class);
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
        $renderer1 = $this->createMock(LayoutRendererInterface::class);
        $renderer2 = $this->createMock(LayoutRendererInterface::class);
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
        $type = $this->createMock(BlockTypeInterface::class);

        $extension = $this->createMock(ExtensionInterface::class);
        $layoutFactory = $this->layoutFactoryBuilder
            ->addExtension($extension)
            ->getLayoutFactory();

        $extension->expects($this->once())
            ->method('hasType')
            ->with($name)
            ->willReturn(true);
        $extension->expects($this->once())
            ->method('getType')
            ->with($name)
            ->willReturn($type);

        $this->assertSame(
            $type,
            $layoutFactory->getRegistry()->getType($name)
        );
    }

    public function testAddType()
    {
        $name = 'test';
        $type = $this->createMock(BlockTypeInterface::class);
        $type->expects($this->once())
            ->method('getName')
            ->willReturn($name);

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
        $name = 'test';
        $typeExtension = $this->createMock(BlockTypeExtensionInterface::class);
        $typeExtension->expects($this->once())
            ->method('getExtendedType')
            ->willReturn($name);
        $blockBuilder = $this->createMock(BlockBuilderInterface::class);

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
        $id = 'test';
        $layoutUpdate = $this->createMock(LayoutUpdateInterface::class);
        $layoutManipulator = $this->createMock(DeferredLayoutManipulatorInterface::class);
        $layoutItem = $this->createMock(LayoutItemInterface::class);
        $layoutItem->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        $layoutFactory = $this->layoutFactoryBuilder
            ->addLayoutUpdate($id, $layoutUpdate)
            ->getLayoutFactory();

        $layoutUpdate->expects($this->once())
            ->method('updateLayout')
            ->with($this->identicalTo($layoutManipulator), $this->identicalTo($layoutItem));

        $layoutFactory->getRegistry()->updateLayout($id, $layoutManipulator, $layoutItem);
    }
}
