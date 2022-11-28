<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Cache;

use Oro\Bundle\LayoutBundle\Cache\Extension\RenderCacheExtensionInterface;
use Oro\Bundle\LayoutBundle\Cache\Metadata\CacheMetadataProviderInterface;
use Oro\Bundle\LayoutBundle\Cache\Metadata\LayoutCacheMetadata;
use Oro\Bundle\LayoutBundle\Cache\RenderCache;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\LayoutContext;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RenderCacheTest extends TestCase
{
    private TagAwareAdapterInterface|\PHPUnit\Framework\MockObject\MockObject $cache;

    private CacheMetadataProviderInterface|\PHPUnit\Framework\MockObject\MockObject $metadataProvider;

    private RequestStack|\PHPUnit\Framework\MockObject\MockObject $requestStack;

    private RenderCache $renderCache;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(TagAwareAdapterInterface::class);
        $this->metadataProvider = $this->createMock(CacheMetadataProviderInterface::class);
        $this->requestStack = new RequestStack();

        $this->renderCache = new RenderCache(
            $this->cache,
            $this->metadataProvider,
            $this->requestStack,
            [$this->createMock(RenderCacheExtensionInterface::class)]
        );
    }

    /**
     * @dataProvider isEnabledProvider
     */
    public function testIsEnabled(string $httpMethod, bool $enabled): void
    {
        $this->requestStack->push(Request::create('', $httpMethod));
        self::assertEquals($enabled, $this->renderCache->isEnabled());
    }

    public function isEnabledProvider(): array
    {
        return [
            ['POST', false],
            ['PUT', false],
            ['GET', true],
            ['HEAD', true],
        ];
    }

    public function testIsCached(): void
    {
        $this->requestStack->push(Request::create('', 'GET'));

        $context = new LayoutContext();
        $blockView = new BlockView();
        $blockView->vars['id'] = 'block_id';
        $layoutCacheMetadata = new LayoutCacheMetadata();
        $this->metadataProvider->expects(self::exactly(3))
            ->method('getCacheMetadata')
            ->with($blockView, $context)
            ->willReturn($layoutCacheMetadata);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(true);
        $cacheItem->expects(self::any())
            ->method('getKey')
            ->willReturn('block_id');

        $this->cache->expects(self::once())
            ->method('getItem')
            ->willReturn($cacheItem);

        self::assertTrue($this->renderCache->isCached($blockView, $context));
        // fetch item after isCached must not trigger additional calls of the cache
        $this->renderCache->getItem($blockView, $context);
    }

    public function testGetItem(): void
    {
        $this->requestStack->push(Request::create('', 'GET'));

        $context = new LayoutContext();
        $blockView = new BlockView();
        $blockView->vars['id'] = 'block_id';
        $layoutCacheMetadata = new LayoutCacheMetadata();
        $this->metadataProvider->expects(self::once())
            ->method('getCacheMetadata')
            ->with($blockView)
            ->willReturn($layoutCacheMetadata);

        $cacheItem = $this->createMock(CacheItemInterface::class);

        $this->cache->expects(self::once())
            ->method('getItem')
            ->willReturn($cacheItem);

        self::assertSame(
            $cacheItem,
            $this->renderCache->getItem($blockView, $context)
        );
    }

    public function testSave(): void
    {
        $cacheItem = new CacheItem();

        $this->cache->expects(self::once())
            ->method('save')
            ->with($cacheItem)
            ->willReturn(true);

        self::assertTrue($this->renderCache->save($cacheItem));
    }

    public function testGetMetadata(): void
    {
        $context = new LayoutContext();
        $blockView = new BlockView();
        $blockView->vars['id'] = 'block_id';
        $layoutCacheMetadata = new LayoutCacheMetadata();
        $this->metadataProvider->expects(self::once())
            ->method('getCacheMetadata')
            ->with($blockView, $context)
            ->willReturn($layoutCacheMetadata);
        self::assertSame(
            $layoutCacheMetadata,
            $this->renderCache->getMetadata($blockView, $context)
        );
    }
}
