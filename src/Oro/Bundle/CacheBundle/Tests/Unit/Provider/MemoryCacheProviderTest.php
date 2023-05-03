<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Provider;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\CacheBundle\Provider\MemoryCacheProvider;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\ItemInterface;

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
            ->willReturn($cacheItem = $this->createMock(ItemInterface::class));

        $cacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(true);

        $cacheItem->expects($this->once())
            ->method('get')
            ->willReturn($cachedData = 'sample_data');

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
            ->willReturn($cacheItem = $this->createMock(ItemInterface::class));

        $cacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(false);

        $cacheItem->expects($this->never())
            ->method('get');

        $this->assertNull($this->provider->get($cacheKeyArguments));
    }

    public function testReset(): void
    {
        $this->arrayAdapter->expects($this->once())
            ->method('reset');

        $this->provider->reset();
    }
}
