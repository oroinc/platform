<?php

declare(strict_types=1);

namespace Oro\Bundle\SyncBundle\Tests\Unit\WebsocketServerState;

use Oro\Bundle\SyncBundle\WebsocketServerState\WebsocketServerCacheStateManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

final class WebsocketServerCacheStateManagerTest extends TestCase
{
    private CacheItemPoolInterface&MockObject $cachePool;
    private WebsocketServerCacheStateManager $manager;

    protected function setUp(): void
    {
        $this->cachePool = $this->createMock(CacheItemPoolInterface::class);
        $this->manager = new WebsocketServerCacheStateManager($this->cachePool);
    }

    public function testUpdateStateCreatesAndSavesNewTimestamp(): void
    {
        $stateId = 'test_state_id';
        $cacheItem = $this->createMock(CacheItemInterface::class);

        $this->cachePool->expects(self::once())
            ->method('getItem')
            ->with($stateId)
            ->willReturn($cacheItem);

        $cacheItem->expects(self::once())
            ->method('set')
            ->with(self::isInstanceOf(\DateTimeInterface::class));

        $this->cachePool->expects(self::once())
            ->method('save')
            ->with($cacheItem)
            ->willReturn(true);

        $result = $this->manager->updateState($stateId);

        self::assertEquals('UTC', $result->getTimezone()->getName());
    }

    public function testGetStateReturnsDateTimeWhenCacheHit(): void
    {
        $stateId = 'test_state_id';
        $expectedDate = new \DateTime('2024-01-15 10:30:00', new \DateTimeZone('UTC'));
        $cacheItem = $this->createMock(CacheItemInterface::class);

        $this->cachePool->expects(self::once())
            ->method('getItem')
            ->with($stateId)
            ->willReturn($cacheItem);

        $cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(true);

        $cacheItem->expects(self::once())
            ->method('get')
            ->willReturn($expectedDate);

        $result = $this->manager->getState($stateId);

        self::assertSame($expectedDate, $result);
    }

    public function testGetStateReturnsNullWhenCacheMiss(): void
    {
        $stateId = 'test_state_id';
        $cacheItem = $this->createMock(CacheItemInterface::class);

        $this->cachePool->expects(self::once())
            ->method('getItem')
            ->with($stateId)
            ->willReturn($cacheItem);

        $cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(false);

        $cacheItem->expects(self::never())
            ->method('get');

        $result = $this->manager->getState($stateId);

        self::assertNull($result);
    }
}
