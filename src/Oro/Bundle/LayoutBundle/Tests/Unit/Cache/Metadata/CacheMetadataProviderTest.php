<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Cache\Metadata;

use Oro\Bundle\LayoutBundle\Cache\Metadata\CacheMetadataProvider;
use Oro\Bundle\LayoutBundle\Cache\Metadata\CacheMetadataProviderInterface;
use Oro\Bundle\LayoutBundle\Cache\Metadata\LayoutCacheMetadata;
use Oro\Bundle\LayoutBundle\Exception\InvalidLayoutCacheMetadataException;
use Oro\Bundle\LayoutBundle\Layout\LayoutContextHolder;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\ContextInterface;
use Psr\Log\LoggerInterface;

class CacheMetadataProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var CacheMetadataProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $defaultProvider;

    /** @var CacheMetadataProviderInterface[]|\PHPUnit\Framework\MockObject\MockObject[] */
    private $providers;

    /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $context;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var CacheMetadataProvider */
    private $cacheMetadataProvider;

    protected function setUp(): void
    {
        $this->defaultProvider = $this->createMock(CacheMetadataProviderInterface::class);
        $provider1 = $this->createMock(CacheMetadataProviderInterface::class);
        $provider2 = $this->createMock(CacheMetadataProviderInterface::class);
        $this->providers = [$provider1, $provider2];
        $this->context = $this->createMock(ContextInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $contextHolder = $this->createMock(LayoutContextHolder::class);
        $contextHolder->expects($this->any())
            ->method('getContext')
            ->willReturn($this->context);

        $this->cacheMetadataProvider = new CacheMetadataProvider(
            $this->defaultProvider,
            $this->providers,
            $contextHolder,
            $this->logger,
            false
        );
    }

    public function testGetCacheMetadataNull(): void
    {
        $blockView = new BlockView();
        $blockView->vars['cache_key'] = 'cache key';

        $metadata = $this->cacheMetadataProvider->getCacheMetadata($blockView);
        $this->assertNull($metadata);
    }

    public function testGetCacheMetadataFromDefaultProvider(): void
    {
        $blockView = new BlockView();
        $blockView->vars['cache_key'] = 'cache key';

        $metadata = new LayoutCacheMetadata();
        $this->defaultProvider->expects($this->once())
            ->method('getCacheMetadata')
            ->willReturn($metadata);

        $this->assertSame($metadata, $this->cacheMetadataProvider->getCacheMetadata($blockView));
    }

    public function testGetCacheMetadataFromCustomProvider(): void
    {
        $blockView = new BlockView();
        $blockView->vars['cache_key'] = 'cache key';

        $metadata = new LayoutCacheMetadata();
        $this->providers[1]->expects($this->once())
            ->method('getCacheMetadata')
            ->willReturn($metadata);

        $this->assertSame($metadata, $this->cacheMetadataProvider->getCacheMetadata($blockView));
    }

    public function testGetCacheMetadataException(): void
    {
        $blockView = new BlockView();
        $blockView->vars['id'] = 'blockID';
        $blockView->vars['cache_key'] = 'cache key';

        $exception = new InvalidLayoutCacheMetadataException('error message');
        $this->defaultProvider->expects($this->once())
            ->method('getCacheMetadata')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Cannot cache the layout block "{id}", the cache metadata is invalid.',
                ['id' => 'blockID', 'exception' => $exception]
            );

        $this->assertNull($this->cacheMetadataProvider->getCacheMetadata($blockView));
    }

    public function testGetCacheMetadataCached(): void
    {
        $blockView = new BlockView();
        $blockView->vars['cache_key'] = 'cache key';
        $metadata = new LayoutCacheMetadata();

        $this->providers[0]->expects($this->once())
            ->method('getCacheMetadata')
            ->willReturn($metadata);

        $this->assertSame($metadata, $this->cacheMetadataProvider->getCacheMetadata($blockView));
        $this->assertSame($metadata, $this->cacheMetadataProvider->getCacheMetadata($blockView));
    }

    public function testReset(): void
    {
        $blockView = new BlockView();
        $blockView->vars['cache_key'] = 'cache key';

        $this->providers[0]->expects($this->exactly(2))
            ->method('getCacheMetadata');

        $this->assertNull($this->cacheMetadataProvider->getCacheMetadata($blockView));
        $this->assertNull($this->cacheMetadataProvider->getCacheMetadata($blockView));
        $this->cacheMetadataProvider->reset();
        $this->assertNull($this->cacheMetadataProvider->getCacheMetadata($blockView));
    }
}
