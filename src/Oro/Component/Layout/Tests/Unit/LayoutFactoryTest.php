<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockFactoryInterface;
use Oro\Component\Layout\BlockTypeInterface;
use Oro\Component\Layout\DeferredLayoutManipulatorInterface;
use Oro\Component\Layout\ExpressionLanguage\ExpressionProcessor;
use Oro\Component\Layout\LayoutBuilderInterface;
use Oro\Component\Layout\LayoutFactory;
use Oro\Component\Layout\LayoutRegistryInterface;
use Oro\Component\Layout\LayoutRendererRegistryInterface;
use Oro\Component\Layout\RawLayoutBuilderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LayoutFactoryTest extends TestCase
{
    private LayoutRegistryInterface&MockObject $registry;
    private LayoutRendererRegistryInterface&MockObject $rendererRegistry;
    private ExpressionProcessor&MockObject $expressionProcessor;
    private LayoutFactory $layoutFactory;

    #[\Override]
    protected function setUp(): void
    {
        $this->registry = $this->createMock(LayoutRegistryInterface::class);
        $this->rendererRegistry = $this->createMock(LayoutRendererRegistryInterface::class);
        $this->expressionProcessor = $this->createMock(ExpressionProcessor::class);

        $this->layoutFactory = new LayoutFactory(
            $this->registry,
            $this->rendererRegistry,
            $this->expressionProcessor
        );
    }

    public function testGetRegistry(): void
    {
        $this->assertSame($this->registry, $this->layoutFactory->getRegistry());
    }

    public function testGetRendererRegistry(): void
    {
        $this->assertSame($this->rendererRegistry, $this->layoutFactory->getRendererRegistry());
    }

    public function testGetType(): void
    {
        $name = 'test';
        $type = $this->createMock(BlockTypeInterface::class);

        $this->registry->expects($this->once())
            ->method('getType')
            ->with($name)
            ->willReturn($type);

        $this->assertSame($type, $this->layoutFactory->getType($name));
    }

    public function testCreateRawLayoutBuilder(): void
    {
        $this->assertInstanceOf(
            RawLayoutBuilderInterface::class,
            $this->layoutFactory->createRawLayoutBuilder()
        );
    }

    public function testCreateLayoutManipulator(): void
    {
        $rawLayoutBuilder = $this->createMock(RawLayoutBuilderInterface::class);

        $this->assertInstanceOf(
            DeferredLayoutManipulatorInterface::class,
            $this->layoutFactory->createLayoutManipulator($rawLayoutBuilder)
        );
    }

    public function testCreateBlockFactory(): void
    {
        $layoutManipulator = $this->createMock(DeferredLayoutManipulatorInterface::class);

        $this->assertInstanceOf(
            BlockFactoryInterface::class,
            $this->layoutFactory->createBlockFactory($layoutManipulator)
        );
    }

    public function testCreateLayoutBuilder(): void
    {
        $this->assertInstanceOf(
            LayoutBuilderInterface::class,
            $this->layoutFactory->createLayoutBuilder()
        );
    }
}
