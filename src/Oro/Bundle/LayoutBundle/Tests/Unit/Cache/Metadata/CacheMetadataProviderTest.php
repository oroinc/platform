<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Cache\Metadata;

use Oro\Bundle\LayoutBundle\Cache\Metadata\CacheMetadataProvider;
use Oro\Bundle\LayoutBundle\Cache\Metadata\CacheMetadataProviderInterface;
use Oro\Bundle\LayoutBundle\Cache\Metadata\LayoutCacheMetadata;
use Oro\Bundle\LayoutBundle\Exception\InvalidLayoutCacheMetadataException;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\LayoutContext;
use Psr\Log\LoggerInterface;

class CacheMetadataProviderTest extends \PHPUnit\Framework\TestCase
{
    private CacheMetadataProviderInterface|\PHPUnit\Framework\MockObject\MockObject $defaultProvider;

    /** @var CacheMetadataProviderInterface[]|\PHPUnit\Framework\MockObject\MockObject[] */
    private array $providers;

    private LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger;

    private CacheMetadataProvider $cacheMetadataProvider;

    protected function setUp(): void
    {
        $this->defaultProvider = $this->createMock(CacheMetadataProviderInterface::class);
        $provider1 = $this->createMock(CacheMetadataProviderInterface::class);
        $provider2 = $this->createMock(CacheMetadataProviderInterface::class);
        $this->providers = [$provider1, $provider2];
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->cacheMetadataProvider = new CacheMetadataProvider(
            $this->defaultProvider,
            $this->providers,
            $this->logger,
            false
        );
    }

    public function testGetCacheMetadataNull(): void
    {
        $context = new LayoutContext();
        $blockView = new BlockView();
        $blockView->vars['cache_key'] = 'cache key';

        $metadata = $this->cacheMetadataProvider->getCacheMetadata($blockView, $context);
        self::assertNull($metadata);
    }

    public function testGetCacheMetadataFromDefaultProvider(): void
    {
        $context = new LayoutContext();
        $blockView = new BlockView();
        $blockView->vars['cache_key'] = 'cache key';

        $metadata = new LayoutCacheMetadata();
        $this->defaultProvider->expects(self::once())
            ->method('getCacheMetadata')
            ->with($blockView, $context)
            ->willReturn($metadata);

        self::assertSame($metadata, $this->cacheMetadataProvider->getCacheMetadata($blockView, $context));
    }

    public function testGetCacheMetadataFromCustomProvider(): void
    {
        $context = new LayoutContext();
        $blockView = new BlockView();
        $blockView->vars['cache_key'] = 'cache key';

        $metadata = new LayoutCacheMetadata();
        $this->providers[1]->expects(self::once())
            ->method('getCacheMetadata')
            ->with($blockView, $context)
            ->willReturn($metadata);

        self::assertSame($metadata, $this->cacheMetadataProvider->getCacheMetadata($blockView, $context));
    }

    public function testGetCacheMetadataException(): void
    {
        $context = new LayoutContext();
        $blockView = new BlockView();
        $blockView->vars['id'] = 'blockID';
        $blockView->vars['cache_key'] = 'cache key';

        $exception = new InvalidLayoutCacheMetadataException('error message');
        $this->defaultProvider->expects(self::once())
            ->method('getCacheMetadata')
            ->with($blockView, $context)
            ->willThrowException($exception);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Cannot cache the layout block "{id}", the cache metadata is invalid.',
                ['id' => 'blockID', 'exception' => $exception]
            );

        self::assertNull($this->cacheMetadataProvider->getCacheMetadata($blockView, $context));
    }

    public function testGetCacheMetadataCached(): void
    {
        $context = new LayoutContext();
        $blockView = new BlockView();
        $blockView->vars['cache_key'] = 'cache key';
        $metadata = new LayoutCacheMetadata();

        $this->providers[0]->expects(self::once())
            ->method('getCacheMetadata')
            ->with($blockView, $context)
            ->willReturn($metadata);

        self::assertSame($metadata, $this->cacheMetadataProvider->getCacheMetadata($blockView, $context));
        self::assertSame($metadata, $this->cacheMetadataProvider->getCacheMetadata($blockView, $context));
    }

    public function testReset(): void
    {
        $context = new LayoutContext();
        $blockView = new BlockView();
        $blockView->vars['cache_key'] = 'cache key';

        $this->providers[0]->expects(self::exactly(2))
            ->method('getCacheMetadata')
            ->with($blockView, $context);

        self::assertNull($this->cacheMetadataProvider->getCacheMetadata($blockView, $context));
        self::assertNull($this->cacheMetadataProvider->getCacheMetadata($blockView, $context));
        $this->cacheMetadataProvider->reset();
        self::assertNull($this->cacheMetadataProvider->getCacheMetadata($blockView, $context));
    }
}
