<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Asset;

use Oro\Bundle\UIBundle\Asset\DynamicAssetVersionManager;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class DynamicAssetVersionManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var CacheItemPoolInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var CacheItemInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheItem;

    /** @var DynamicAssetVersionManager */
    private $assetVersionManager;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
        $this->cacheItem = $this->createMock(CacheItemInterface::class);
        $this->assetVersionManager = new DynamicAssetVersionManager($this->cache);
    }

    public function testGetAssetVersionWithEmptyCache()
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

    public function testGetAssetVersion()
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

    public function testUpdateAssetVersionWithEmptyCache()
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

    public function testUpdateAssetVersion()
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
