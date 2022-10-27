<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Cache;

use Oro\Bundle\SecurityBundle\Acl\Cache\UnderlyingAclCache;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

class UnderlyingAclCacheTest extends \PHPUnit\Framework\TestCase
{
    /** @var UnderlyingAclCache */
    private $cache;

    /** @var CacheItemPoolInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheProvider;

    /** @var CacheItemInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheItem;

    protected function setUp(): void
    {
        $this->cacheProvider = $this->createMock(CacheItemPoolInterface::class);
        $this->cacheItem = $this->createMock(CacheItemInterface::class);
        $this->cache = new UnderlyingAclCache($this->cacheProvider);
    }

    public function testCacheUnderlyingWithIntegerOID1ShouldBeSavedIn1Batch()
    {
        $oid = new ObjectIdentity(1, \stdClass::class);
        $this->expectCacheSave('stdClass_1', [1 => true]);

        $this->cache->cacheUnderlying($oid);
    }

    public function testCacheUnderlyingWithIntegerOID3200ShouldBeSavedIn4Batch()
    {
        $oid = new ObjectIdentity(3200, \stdClass::class);
        $this->expectCacheSave('stdClass_4', [3200 => true]);

        $this->cache->cacheUnderlying($oid);
    }

    public function testCacheUnderlyingWithIntegerOID140000ShouldBeSavedIn141Batch()
    {
        $oid = new ObjectIdentity(140000, \stdClass::class);
        $this->expectCacheSave('stdClass_141', [140000 => true]);

        $this->cache->cacheUnderlying($oid);
    }

    public function testCacheUnderlyingWithStringOIDaaaShouldBeSavedIn4027021Batch()
    {
        $oid = new ObjectIdentity('aaa', \stdClass::class);
        $this->expectCacheSave('stdClass_4027021', ['aaa' => true]);

        $this->cache->cacheUnderlying($oid);
    }

    public function testCacheUnderlyingWithTestGUIDStringOIDShouldBeSavedIn1800856Batch()
    {
        $oid = new ObjectIdentity('08a9f12f-9932-4889-adcd-995f37550da9', \stdClass::class);
        $this->expectCacheSave('stdClass_1800856', ['08a9f12f-9932-4889-adcd-995f37550da9' => true]);

        $this->cache->cacheUnderlying($oid);
    }

    public function testCacheUnderlyingWithAnotherTestGUIDStringOIDShouldBeSavedIn2980375Batch()
    {
        $oid = new ObjectIdentity('1034649d-01ff-44c4-b2d1-d5e2f6cc83bd', \stdClass::class);
        $this->expectCacheSave('stdClass_2980375', ['1034649d-01ff-44c4-b2d1-d5e2f6cc83bd' => true]);

        $this->cache->cacheUnderlying($oid);
    }

    private function expectCacheSave($key, $value): void
    {
        $this->cacheProvider->expects($this->once())
            ->method('getItem')
            ->with($key)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(false);
        $this->cacheItem->expects($this->once())
            ->method('set')
            ->with($value)
            ->willReturn($this->cacheItem);
        $this->cacheProvider->expects($this->once())
            ->method('save')
            ->with($this->cacheItem);
    }
}
