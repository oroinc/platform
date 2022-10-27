<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout;

use Oro\Bundle\LayoutBundle\Cache\RenderCache;
use Oro\Bundle\LayoutBundle\Layout\CacheLayoutBuilder;
use Oro\Component\Layout\BlockFactoryInterface;
use Oro\Component\Layout\BlockViewCache;
use Oro\Component\Layout\DeferredLayoutManipulatorInterface;
use Oro\Component\Layout\ExpressionLanguage\ExpressionProcessor;
use Oro\Component\Layout\LayoutRegistryInterface;
use Oro\Component\Layout\LayoutRendererInterface;
use Oro\Component\Layout\LayoutRendererRegistry;
use Oro\Component\Layout\RawLayoutBuilderInterface;
use Oro\Component\Layout\Tests\Unit\LayoutBuilderTest;

class CacheLayoutBuilderTest extends LayoutBuilderTest
{
    protected function setUp(): void
    {
        $this->registry = $this->createMock(LayoutRegistryInterface::class);
        $this->rawLayoutBuilder = $this->createMock(RawLayoutBuilderInterface::class);
        $this->layoutManipulator = $this->createMock(DeferredLayoutManipulatorInterface::class);
        $this->blockFactory = $this->createMock(BlockFactoryInterface::class);
        $this->renderer = $this->createMock(LayoutRendererInterface::class);
        $this->expressionProcessor = $this->createMock(ExpressionProcessor::class);
        $this->blockViewCache = $this->createMock(BlockViewCache::class);

        $rendererRegistry = new LayoutRendererRegistry();
        $rendererRegistry->addRenderer('test', $this->renderer);
        $rendererRegistry->setDefaultRenderer('test');

        $renderCache = $this->createMock(RenderCache::class);
        $this->layoutBuilder = $this->getMockBuilder(CacheLayoutBuilder::class)
            ->setConstructorArgs([
                $this->registry,
                $this->rawLayoutBuilder,
                $this->layoutManipulator,
                $this->blockFactory,
                $rendererRegistry,
                $this->expressionProcessor,
                $renderCache,
                $this->blockViewCache,
            ])
            ->onlyMethods(['createLayout'])
            ->getMock();

        $this->layoutBuilderWithoutCache = $this->getMockBuilder(CacheLayoutBuilder::class)
            ->setConstructorArgs([
                $this->registry,
                $this->rawLayoutBuilder,
                $this->layoutManipulator,
                $this->blockFactory,
                $rendererRegistry,
                $this->expressionProcessor,
                $renderCache,
            ])
            ->onlyMethods(['createLayout'])
            ->getMock();
    }
}
