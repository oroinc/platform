<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\ApiBundle\Provider\ConfigCacheStateRegistry;
use Oro\Bundle\ApiBundle\Provider\ResourcesCacheAccessor;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\Config\Cache\ConfigCacheStateInterface;

class ResourcesCacheAccessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|CacheProvider */
    private $cache;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigCacheStateRegistry */
    private $configCacheStateRegistry;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheProvider::class);
        $this->configCacheStateRegistry = $this->createMock(ConfigCacheStateRegistry::class);
    }

    private function getCacheAccessor(bool $withoutConfigCacheStateRegistry = false): ResourcesCacheAccessor
    {
        $cacheAccessor = new ResourcesCacheAccessor($this->cache);
        if (!$withoutConfigCacheStateRegistry) {
            $cacheAccessor->setConfigCacheStateRegistry($this->configCacheStateRegistry);
        }

        return $cacheAccessor;
    }

    public function testClear()
    {
        $this->cache->expects(self::once())
            ->method('deleteAll');

        $cacheAccessor = $this->getCacheAccessor();

        $cacheAccessor->clear();
    }

    public function testFetchWhenNoCachedData()
    {
        $version = '1.2';
        $requestType = new RequestType([RequestType::REST]);
        $id = 'test';

        $this->cache->expects(self::once())
            ->method('fetch')
            ->with('test1.2rest')
            ->willReturn(false);

        $this->configCacheStateRegistry->expects(self::never())
            ->method('getConfigCacheState');

        $cacheAccessor = $this->getCacheAccessor();

        self::assertFalse(
            $cacheAccessor->fetch($version, $requestType, $id)
        );
    }

    public function testFetchWithoutConfigCacheStateRegistry()
    {
        $version = '1.2';
        $requestType = new RequestType([RequestType::REST]);
        $id = 'test';
        $data = ['key' => 'value'];

        $this->cache->expects(self::once())
            ->method('fetch')
            ->with('test1.2rest')
            ->willReturn([null, $data]);

        $cacheAccessor = $this->getCacheAccessor(true);

        self::assertSame(
            $data,
            $cacheAccessor->fetch($version, $requestType, $id)
        );
    }

    public function testFetchWhenConfigCacheTimestampIsNull()
    {
        $version = '1.2';
        $requestType = new RequestType([RequestType::REST]);
        $id = 'test';
        $data = ['key' => 'value'];

        $this->cache->expects(self::once())
            ->method('fetch')
            ->with('test1.2rest')
            ->willReturn([null, $data]);

        $configCacheState = $this->createMock(ConfigCacheStateInterface::class);
        $this->configCacheStateRegistry->expects(self::once())
            ->method('getConfigCacheState')
            ->with(self::identicalTo($requestType))
            ->willReturn($configCacheState);
        $configCacheState->expects(self::once())
            ->method('isCacheFresh')
            ->with(self::isNull())
            ->willReturn(true);

        $cacheAccessor = $this->getCacheAccessor();

        self::assertSame(
            $data,
            $cacheAccessor->fetch($version, $requestType, $id)
        );
    }

    public function testFetchWhenConfigCacheIsFresh()
    {
        $version = '1.2';
        $requestType = new RequestType([RequestType::REST]);
        $id = 'test';
        $timestamp = 123;
        $data = ['key' => 'value'];

        $this->cache->expects(self::once())
            ->method('fetch')
            ->with('test1.2rest')
            ->willReturn([$timestamp, $data]);

        $configCacheState = $this->createMock(ConfigCacheStateInterface::class);
        $this->configCacheStateRegistry->expects(self::once())
            ->method('getConfigCacheState')
            ->with(self::identicalTo($requestType))
            ->willReturn($configCacheState);
        $configCacheState->expects(self::once())
            ->method('isCacheFresh')
            ->with($timestamp)
            ->willReturn(true);

        $cacheAccessor = $this->getCacheAccessor();

        self::assertSame(
            $data,
            $cacheAccessor->fetch($version, $requestType, $id)
        );
    }

    public function testFetchWhenConfigCacheIsDirty()
    {
        $version = '1.2';
        $requestType = new RequestType([RequestType::REST]);
        $id = 'test';
        $timestamp = 123;
        $data = ['key' => 'value'];

        $this->cache->expects(self::once())
            ->method('fetch')
            ->with('test1.2rest')
            ->willReturn([$timestamp, $data]);

        $configCacheState = $this->createMock(ConfigCacheStateInterface::class);
        $this->configCacheStateRegistry->expects(self::once())
            ->method('getConfigCacheState')
            ->with(self::identicalTo($requestType))
            ->willReturn($configCacheState);
        $configCacheState->expects(self::once())
            ->method('isCacheFresh')
            ->with($timestamp)
            ->willReturn(false);

        $cacheAccessor = $this->getCacheAccessor();

        self::assertFalse(
            $cacheAccessor->fetch($version, $requestType, $id)
        );
    }

    public function testSaveWithoutConfigCacheStateRegistry()
    {
        $version = '1.2';
        $requestType = new RequestType([RequestType::REST]);
        $id = 'test';
        $data = ['key' => 'value'];

        $this->cache->expects(self::once())
            ->method('save')
            ->with('test1.2rest', [null, $data]);

        $cacheAccessor = $this->getCacheAccessor(true);

        $cacheAccessor->save($version, $requestType, $id, $data);
    }

    public function testSave()
    {
        $version = '1.2';
        $requestType = new RequestType([RequestType::REST]);
        $id = 'test';
        $timestamp = 123;
        $data = ['key' => 'value'];

        $this->cache->expects(self::once())
            ->method('save')
            ->with('test1.2rest', [$timestamp, $data]);

        $configCacheState = $this->createMock(ConfigCacheStateInterface::class);
        $this->configCacheStateRegistry->expects(self::once())
            ->method('getConfigCacheState')
            ->with(self::identicalTo($requestType))
            ->willReturn($configCacheState);
        $configCacheState->expects(self::once())
            ->method('getCacheTimestamp')
            ->willReturn($timestamp);

        $cacheAccessor = $this->getCacheAccessor();

        $cacheAccessor->save($version, $requestType, $id, $data);
    }
}
