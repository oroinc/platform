<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout;

use Oro\Bundle\LayoutBundle\Cache\Metadata\CacheMetadataProvider;
use Oro\Bundle\LayoutBundle\Cache\RenderCache;
use Oro\Bundle\LayoutBundle\Layout\CacheLayoutFactory;
use Oro\Bundle\LayoutBundle\Layout\CacheLayoutFactoryBuilder;
use Oro\Component\Layout\BlockViewCache;
use Oro\Component\Layout\ExpressionLanguage\ExpressionProcessor;

class CacheLayoutFactoryBuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var CacheMetadataProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheMetadataProvider;

    /** @var CacheLayoutFactoryBuilder */
    private $cacheLayoutFactoryBuilder;

    protected function setUp(): void
    {
        $renderCache = $this->createMock(RenderCache::class);
        $this->cacheMetadataProvider = $this->createMock(CacheMetadataProvider::class);
        $expressionProcessor = $this->createMock(ExpressionProcessor::class);
        $blockViewCache = $this->createMock(BlockViewCache::class);
        $this->cacheLayoutFactoryBuilder = new CacheLayoutFactoryBuilder(
            $expressionProcessor,
            $renderCache,
            $this->cacheMetadataProvider,
            $blockViewCache
        );
        $this->cacheLayoutFactoryBuilder->setDebug(true);
    }

    public function testGetLayoutFactory(): void
    {
        $this->cacheMetadataProvider->expects($this->once())
            ->method('reset');
        $this->assertInstanceOf(
            CacheLayoutFactory::class,
            $this->cacheLayoutFactoryBuilder->getLayoutFactory()
        );
    }
}
