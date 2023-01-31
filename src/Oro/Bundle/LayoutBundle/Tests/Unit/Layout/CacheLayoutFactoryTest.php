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
use Oro\Component\Layout\LayoutContextStack;
use Oro\Component\Layout\LayoutFactoryInterface;
use Oro\Component\Layout\LayoutRegistryInterface;
use Oro\Component\Layout\LayoutRendererRegistryInterface;
use Oro\Component\Layout\RawLayoutBuilderInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CacheLayoutFactoryTest extends \PHPUnit\Framework\TestCase
{
    private LayoutFactoryInterface|\PHPUnit\Framework\MockObject\MockObject $baseLayoutFactory;

    private CacheLayoutFactory $cacheLayoutFactory;

    protected function setUp(): void
    {
        $this->baseLayoutFactory = $this->createMock(LayoutFactoryInterface::class);
        $layoutContextStack = new LayoutContextStack();
        $expressionProcessor = $this->createMock(ExpressionProcessor::class);
        $renderCache = $this->createMock(RenderCache::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $blockViewCache = $this->createMock(BlockViewCache::class);

        $this->cacheLayoutFactory = new CacheLayoutFactory(
            $this->baseLayoutFactory,
            $layoutContextStack,
            $expressionProcessor,
            $renderCache,
            $eventDispatcher,
            $blockViewCache
        );
    }

    public function testGetType(): void
    {
        $type = $this->createMock(BlockTypeInterface::class);
        $this->baseLayoutFactory->expects(self::once())
            ->method('getType')
            ->with('type name')
            ->willReturn($type);
        self::assertSame(
            $type,
            $this->cacheLayoutFactory->getType('type name')
        );
    }

    public function testCreateLayoutManipulator(): void
    {
        $layoutManipulator = $this->createMock(DeferredLayoutManipulatorInterface::class);
        $rawLayoutBuilder = $this->createMock(RawLayoutBuilderInterface::class);
        $this->baseLayoutFactory->expects(self::once())
            ->method('createLayoutManipulator')
            ->with($rawLayoutBuilder)
            ->willReturn($layoutManipulator);
        self::assertSame(
            $layoutManipulator,
            $this->cacheLayoutFactory->createLayoutManipulator($rawLayoutBuilder)
        );
    }

    public function testGetRegistry(): void
    {
        $registry = $this->createMock(LayoutRegistryInterface::class);
        $this->baseLayoutFactory->expects(self::once())
            ->method('getRegistry')
            ->willReturn($registry);
        self::assertSame(
            $registry,
            $this->cacheLayoutFactory->getRegistry()
        );
    }

    public function testCreateRawLayoutBuilder(): void
    {
        $rawLayoutBuilder = $this->createMock(RawLayoutBuilderInterface::class);
        $this->baseLayoutFactory->expects(self::once())
            ->method('createRawLayoutBuilder')
            ->willReturn($rawLayoutBuilder);
        self::assertSame(
            $rawLayoutBuilder,
            $this->cacheLayoutFactory->createRawLayoutBuilder()
        );
    }

    public function testGetRendererRegistry(): void
    {
        $rendererRegistry = $this->createMock(LayoutRendererRegistryInterface::class);
        $this->baseLayoutFactory->expects(self::once())
            ->method('getRendererRegistry')
            ->willReturn($rendererRegistry);
        self::assertSame(
            $rendererRegistry,
            $this->cacheLayoutFactory->getRendererRegistry()
        );
    }

    public function testCreateBlockFactory(): void
    {
        $layoutManipulator = $this->createMock(DeferredLayoutManipulatorInterface::class);
        $blockFactory = $this->createMock(BlockFactoryInterface::class);
        $this->baseLayoutFactory->expects(self::once())
            ->method('createBlockFactory')
            ->with($layoutManipulator)
            ->willReturn($blockFactory);
        self::assertSame(
            $blockFactory,
            $this->cacheLayoutFactory->createBlockFactory($layoutManipulator)
        );
    }

    public function testCreateLayoutBuilder(): void
    {
        $layoutManipulator = $this->createMock(DeferredLayoutManipulatorInterface::class);
        $rawLayoutBuilder = $this->createMock(RawLayoutBuilderInterface::class);
        $this->baseLayoutFactory->expects(self::once())
            ->method('createLayoutManipulator')
            ->with($rawLayoutBuilder)
            ->willReturn($layoutManipulator);
        $this->baseLayoutFactory->expects(self::once())
            ->method('createRawLayoutBuilder')
            ->willReturn($rawLayoutBuilder);
        $registry = $this->createMock(LayoutRegistryInterface::class);
        $this->baseLayoutFactory->expects(self::once())
            ->method('getRegistry')
            ->willReturn($registry);
        $rendererRegistry = $this->createMock(LayoutRendererRegistryInterface::class);
        $this->baseLayoutFactory->expects(self::once())
            ->method('getRendererRegistry')
            ->willReturn($rendererRegistry);
        $blockFactory = $this->createMock(BlockFactoryInterface::class);
        $this->baseLayoutFactory->expects(self::once())
            ->method('createBlockFactory')
            ->with($layoutManipulator)
            ->willReturn($blockFactory);

        self::assertInstanceOf(
            CacheLayoutBuilder::class,
            $this->cacheLayoutFactory->createLayoutBuilder()
        );
    }
}
