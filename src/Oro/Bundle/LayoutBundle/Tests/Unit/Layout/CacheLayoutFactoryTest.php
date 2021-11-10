<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout;

use Oro\Bundle\LayoutBundle\Cache\RenderCache;
use Oro\Bundle\LayoutBundle\Layout\CacheLayoutBuilder;
use Oro\Bundle\LayoutBundle\Layout\CacheLayoutFactory;
use Oro\Component\Layout\BlockFactoryInterface;
use Oro\Component\Layout\BlockTypeInterface;
use Oro\Component\Layout\BlockViewCache;
use Oro\Component\Layout\DeferredLayoutManipulatorInterface;
use Oro\Component\Layout\ExpressionLanguage\ExpressionProcessor;
use Oro\Component\Layout\LayoutFactoryInterface;
use Oro\Component\Layout\LayoutRegistryInterface;
use Oro\Component\Layout\LayoutRendererRegistryInterface;
use Oro\Component\Layout\RawLayoutBuilderInterface;

class CacheLayoutFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var LayoutFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $baseLayoutFactory;

    /** @var CacheLayoutFactory */
    private $cacheLayoutFactory;

    protected function setUp(): void
    {
        $this->baseLayoutFactory = $this->createMock(LayoutFactoryInterface::class);
        $expressionProcessor = $this->createMock(ExpressionProcessor::class);
        $renderCache = $this->createMock(RenderCache::class);
        $blockViewCache = $this->createMock(BlockViewCache::class);

        $this->cacheLayoutFactory = new CacheLayoutFactory(
            $this->baseLayoutFactory,
            $expressionProcessor,
            $renderCache,
            $blockViewCache
        );
    }

    public function testGetType(): void
    {
        $type = $this->createMock(BlockTypeInterface::class);
        $this->baseLayoutFactory->expects($this->once())
            ->method('getType')
            ->with('type name')
            ->willReturn($type);
        $this->assertSame(
            $type,
            $this->cacheLayoutFactory->getType('type name')
        );
    }

    public function testCreateLayoutManipulator(): void
    {
        $layoutManipulator = $this->createMock(DeferredLayoutManipulatorInterface::class);
        $rawLayoutBuilder = $this->createMock(RawLayoutBuilderInterface::class);
        $this->baseLayoutFactory->expects($this->once())
            ->method('createLayoutManipulator')
            ->with($rawLayoutBuilder)
            ->willReturn($layoutManipulator);
        $this->assertSame(
            $layoutManipulator,
            $this->cacheLayoutFactory->createLayoutManipulator($rawLayoutBuilder)
        );
    }

    public function testGetRegistry(): void
    {
        $registry = $this->createMock(LayoutRegistryInterface::class);
        $this->baseLayoutFactory->expects($this->once())
            ->method('getRegistry')
            ->willReturn($registry);
        $this->assertSame(
            $registry,
            $this->cacheLayoutFactory->getRegistry()
        );
    }

    public function testCreateRawLayoutBuilder(): void
    {
        $rawLayoutBuilder = $this->createMock(RawLayoutBuilderInterface::class);
        $this->baseLayoutFactory->expects($this->once())
            ->method('createRawLayoutBuilder')
            ->willReturn($rawLayoutBuilder);
        $this->assertSame(
            $rawLayoutBuilder,
            $this->cacheLayoutFactory->createRawLayoutBuilder()
        );
    }

    public function testGetRendererRegistry(): void
    {
        $rendererRegistry = $this->createMock(LayoutRendererRegistryInterface::class);
        $this->baseLayoutFactory->expects($this->once())
            ->method('getRendererRegistry')
            ->willReturn($rendererRegistry);
        $this->assertSame(
            $rendererRegistry,
            $this->cacheLayoutFactory->getRendererRegistry()
        );
    }

    public function testCreateBlockFactory(): void
    {
        $layoutManipulator = $this->createMock(DeferredLayoutManipulatorInterface::class);
        $blockFactory = $this->createMock(BlockFactoryInterface::class);
        $this->baseLayoutFactory->expects($this->once())
            ->method('createBlockFactory')
            ->with($layoutManipulator)
            ->willReturn($blockFactory);
        $this->assertSame(
            $blockFactory,
            $this->cacheLayoutFactory->createBlockFactory($layoutManipulator)
        );
    }

    public function testCreateLayoutBuilder(): void
    {
        $layoutManipulator = $this->createMock(DeferredLayoutManipulatorInterface::class);
        $rawLayoutBuilder = $this->createMock(RawLayoutBuilderInterface::class);
        $this->baseLayoutFactory->expects($this->once())
            ->method('createLayoutManipulator')
            ->with($rawLayoutBuilder)
            ->willReturn($layoutManipulator);
        $this->baseLayoutFactory->expects($this->once())
            ->method('createRawLayoutBuilder')
            ->willReturn($rawLayoutBuilder);
        $registry = $this->createMock(LayoutRegistryInterface::class);
        $this->baseLayoutFactory->expects($this->once())
            ->method('getRegistry')
            ->willReturn($registry);
        $rendererRegistry = $this->createMock(LayoutRendererRegistryInterface::class);
        $this->baseLayoutFactory->expects($this->once())
            ->method('getRendererRegistry')
            ->willReturn($rendererRegistry);
        $blockFactory = $this->createMock(BlockFactoryInterface::class);
        $this->baseLayoutFactory->expects($this->once())
            ->method('createBlockFactory')
            ->with($layoutManipulator)
            ->willReturn($blockFactory);

        $this->assertInstanceOf(
            CacheLayoutBuilder::class,
            $this->cacheLayoutFactory->createLayoutBuilder()
        );
    }
}
