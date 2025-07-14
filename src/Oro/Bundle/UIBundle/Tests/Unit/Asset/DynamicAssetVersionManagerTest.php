<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Asset;

use Oro\Bundle\UIBundle\Asset\DynamicAssetVersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class DynamicAssetVersionManagerTest extends TestCase
{
    private CacheItemPoolInterface&MockObject $cache;
    private CacheItemInterface&MockObject $cacheItem;
    private DynamicAssetVersionManager $assetVersionManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
        $this->cacheItem = $this->createMock(CacheItemInterface::class);
        $this->assetVersionManager = new DynamicAssetVersionManager($this->cache);
    }

    public function testGetAssetVersionWithEmptyCache(): void
    {
        $this->cache->expects($this->once())
            ->method('getItem')
            ->with('test')
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(false);

        $this->assertSame('', $this->assetVersionManager->getAssetVersion('test'));
    }

    public function testGetAssetVersion(): void
    {
        $this->cache->expects($this->once())
            ->method('getItem')
            ->with('test')
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(true);
        $this->cacheItem->expects($this->once())
            ->method('get')
            ->willReturn(123);

        $this->assertSame('123', $this->assetVersionManager->getAssetVersion('test'));
    }

    public function testUpdateAssetVersionWithEmptyCache(): void
    {
        $this->cache->expects($this->exactly(3))
            ->method('getItem')
            ->with('test')
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects($this->once())
            ->method('set')
            ->with(2)
            ->willReturn($this->cacheItem);
        $this->cache->expects($this->once())
            ->method('save')
            ->with($this->cacheItem);
        $this->cacheItem->expects($this->exactly(2))
            ->method('isHit')
            ->willReturn(true);
        $this->cacheItem->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls(1, 2);

        $this->assetVersionManager->updateAssetVersion('test');
        $this->assertSame('2', $this->assetVersionManager->getAssetVersion('test'));
    }

    public function testUpdateAssetVersion(): void
    {
        $this->cache->expects($this->exactly(3))
            ->method('getItem')
            ->with('test')
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects($this->once())
            ->method('set')
            ->with(124)
            ->willReturn($this->cacheItem);
        $this->cache->expects($this->once())
            ->method('save')
            ->with($this->cacheItem);
        $this->cacheItem->expects($this->exactly(2))
            ->method('isHit')
            ->willReturn(true);
        $this->cacheItem->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls(123, 124);

        $this->assetVersionManager->updateAssetVersion('test');
        $this->assertSame('124', $this->assetVersionManager->getAssetVersion('test'));
    }
}
