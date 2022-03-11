<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Metadata;

use Doctrine\Persistence\Proxy;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Tests\Unit\Stub\OwnershipMetadataProviderStub;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Contracts\Cache\ItemInterface;

class AbstractOwnershipMetadataProviderTest extends \PHPUnit\Framework\TestCase
{
    private const SOME_CLASS = \stdClass::class;
    private const UNDEFINED_CLASS = 'UndefinedClass';

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager */
    private $configManager;

    /** @var AbstractAdapter|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var OwnershipMetadataProviderStub */
    private $provider;

    /** @var Config */
    private $config;

    protected function setUp(): void
    {
        $this->provider = new OwnershipMetadataProviderStub($this);
        $this->configManager = $this->provider->getConfigManagerMock();
        $this->cache = $this->provider->getCacheMock();

        $this->config = new Config(new EntityConfigId('ownership', self::SOME_CLASS));
    }

    public function testClearCacheForClassName()
    {
        $this->cache->expects($this->once())
            ->method('delete')
            ->with(self::SOME_CLASS);

        $this->provider->clearCache(self::SOME_CLASS);
    }

    public function testClearCacheForEntityProxyClassName()
    {
        $this->cache->expects($this->once())
            ->method('delete')
            ->with(self::SOME_CLASS);

        $this->provider->clearCache('\\' . Proxy::MARKER . '\\' . self::SOME_CLASS);
    }

    public function testClearCacheAll()
    {
        $this->cache->expects($this->once())
            ->method('clear');

        $this->provider->clearCache();
    }

    public function testGetMetadataWithoutCache()
    {
        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::SOME_CLASS)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('ownership', self::SOME_CLASS)
            ->willReturn($this->config);

        $this->cache->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $this->assertEquals(new OwnershipMetadata(), $this->provider->getMetadata(self::SOME_CLASS));
    }

    public function testGetMetadataForEntityProxy()
    {
        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::SOME_CLASS)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('ownership', self::SOME_CLASS)
            ->willReturn($this->config);

        $this->cache->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $this->assertEquals(
            new OwnershipMetadata(),
            $this->provider->getMetadata('\\' . Proxy::MARKER . '\\' . self::SOME_CLASS)
        );
    }

    public function testGetMetadataForNull()
    {
        $this->configManager->expects($this->never())
            ->method($this->anything());

        $this->cache->expects($this->never())
            ->method($this->anything());

        $metadata = new OwnershipMetadata();
        $this->assertEquals($metadata, $this->provider->getMetadata(null));
    }

    public function testGetMetadataUndefinedClassWithCache()
    {
        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::UNDEFINED_CLASS)
            ->willReturn(false);
        $this->configManager->expects($this->never())
            ->method('getEntityConfig');
        $this->cache->expects(self::exactly(2))
            ->method('get')
            ->with(self::UNDEFINED_CLASS)
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function ($cacheKey, $callback) {
                    $item = $this->createMock(ItemInterface::class);
                    return $callback($item);
                }),
                true
            );

        $metadata = new OwnershipMetadata();
        $providerWithCleanCache = clone $this->provider;

        // no cache
        $this->assertEquals($metadata, $this->provider->getMetadata(self::UNDEFINED_CLASS));
        // local cache
        $this->assertEquals($metadata, $this->provider->getMetadata(self::UNDEFINED_CLASS));
        // cache
        $this->assertEquals($metadata, $providerWithCleanCache->getMetadata(self::UNDEFINED_CLASS));
    }

    public function testWarmUpCacheWithoutClassName()
    {
        $configs = [$this->config];

        $this->configManager->expects($this->once())
            ->method('getConfigs')
            ->willReturn($configs);
        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::SOME_CLASS)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('ownership', self::SOME_CLASS)
            ->willReturn($this->config);
        $this->cache->expects(self::once())
            ->method('get')
            ->with(self::SOME_CLASS)
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $this->provider->warmUpCache();
    }

    public function testWarmUpCacheWithClassName()
    {
        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::SOME_CLASS)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('ownership', self::SOME_CLASS)
            ->willReturn($this->config);
        $this->cache->expects(self::once())
            ->method('get')
            ->with(self::SOME_CLASS)
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $this->provider->warmUpCache(self::SOME_CLASS);
    }

    public function testWarmUpCacheWithEntityProxyClassName()
    {
        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::SOME_CLASS)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('ownership', self::SOME_CLASS)
            ->willReturn($this->config);
        $this->cache->expects(self::once())
            ->method('get')
            ->with(self::SOME_CLASS)
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $this->provider->warmUpCache('\\' . Proxy::MARKER . '\\' . self::SOME_CLASS);
    }
}
