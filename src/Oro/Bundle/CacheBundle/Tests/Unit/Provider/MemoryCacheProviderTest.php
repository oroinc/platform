<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Provider;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\CacheBundle\Provider\MemoryCacheProvider;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\CacheItem;

class MemoryCacheProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var UniversalCacheKeyGenerator|\PHPUnit\Framework\MockObject\MockObject */
    private $universalCacheKeyGenerator;

    /** @var ArrayAdapter|\PHPUnit\Framework\MockObject\MockObject */
    private $arrayAdapter;

    /** @var MemoryCacheProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->universalCacheKeyGenerator = $this->createMock(UniversalCacheKeyGenerator::class);
        $this->arrayAdapter = $this->createMock(ArrayAdapter::class);

        $this->provider = new MemoryCacheProvider($this->universalCacheKeyGenerator, $this->arrayAdapter);
    }

    public function testGetWithCallback(): void
    {
        $this->universalCacheKeyGenerator->expects($this->once())
            ->method('generate')
            ->with($cacheKeyArguments = 'sample_argument')
            ->willReturn($cacheKey = 'sample_key');

        $cachedData = 'sample_data';

        $this->arrayAdapter->expects($this->once())
            ->method('get')
            ->willReturnCallback(function ($key, $callable) use ($cacheKey, $cachedData) {
                $this->assertEquals($cacheKey, $key);
                $this->assertIsCallable($callable);

                return $cachedData;
            });

        $this->assertEquals(
            $cachedData,
            $this->provider->get(
                $cacheKeyArguments,
                function () {
                }
            )
        );
    }

    public function testGetWithoutCallback(): void
    {
        $this->universalCacheKeyGenerator->expects($this->once())
            ->method('generate')
            ->with($cacheKeyArguments = ['sample_argument'])
            ->willReturn($cacheKey = 'sample_key');

        $this->arrayAdapter->expects($this->once())
            ->method('getItem')
            ->with($cacheKey)
            ->willReturn($cacheItem = new CacheItem());

        $r = new \ReflectionProperty($cacheItem, 'isHit');
        $r->setValue($cacheItem, true);
        $cacheItem->set($cachedData = 'sample_data');

        $this->assertEquals($cachedData, $this->provider->get($cacheKeyArguments));
    }

    public function testGetWithoutCallbackWhenNoData(): void
    {
        $this->universalCacheKeyGenerator->expects($this->once())
            ->method('generate')
            ->with($cacheKeyArguments = ['sample_argument'])
            ->willReturn($cacheKey = 'sample_key');

        $this->arrayAdapter->expects($this->once())
            ->method('getItem')
            ->with($cacheKey)
            ->willReturn($cacheItem = new CacheItem());

        $this->assertNull($this->provider->get($cacheKeyArguments));
    }

    public function testReset(): void
    {
        $this->arrayAdapter->expects($this->once())
            ->method('reset');

        $this->provider->reset();
    }
}
