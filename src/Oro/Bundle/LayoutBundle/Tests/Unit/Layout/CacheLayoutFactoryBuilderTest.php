<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout;

use Oro\Bundle\LayoutBundle\Cache\Metadata\CacheMetadataProvider;
use Oro\Bundle\LayoutBundle\Cache\RenderCache;
use Oro\Bundle\LayoutBundle\Layout\CacheLayoutFactory;
use Oro\Bundle\LayoutBundle\Layout\CacheLayoutFactoryBuilder;
use Oro\Component\Layout\BlockViewCache;
use Oro\Component\Layout\ExpressionLanguage\ExpressionProcessor;
use Oro\Component\Layout\LayoutContextStack;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CacheLayoutFactoryBuilderTest extends TestCase
{
    private CacheMetadataProvider&MockObject $cacheMetadataProvider;
    private CacheLayoutFactoryBuilder $cacheLayoutFactoryBuilder;

    #[\Override]
    protected function setUp(): void
    {
        $layoutContextStack = new LayoutContextStack();
        $renderCache = $this->createMock(RenderCache::class);
        $this->cacheMetadataProvider = $this->createMock(CacheMetadataProvider::class);
        $expressionProcessor = $this->createMock(ExpressionProcessor::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $blockViewCache = $this->createMock(BlockViewCache::class);
        $this->cacheLayoutFactoryBuilder = new CacheLayoutFactoryBuilder(
            $layoutContextStack,
            $expressionProcessor,
            $renderCache,
            $this->cacheMetadataProvider,
            $eventDispatcher,
            $blockViewCache
        );
        $this->cacheLayoutFactoryBuilder->setDebug(true);
    }

    public function testGetLayoutFactory(): void
    {
        $this->cacheMetadataProvider->expects(self::once())
            ->method('reset');
        self::assertInstanceOf(
            CacheLayoutFactory::class,
            $this->cacheLayoutFactoryBuilder->getLayoutFactory()
        );
    }
}
