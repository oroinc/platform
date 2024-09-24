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

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var AbstractAdapter|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var Config */
    private $config;

    /** @var OwnershipMetadataProviderStub */
    private $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = new OwnershipMetadataProviderStub($this);
        $this->configManager = $this->provider->getConfigManagerMock();
        $this->cache = $this->provider->getCacheMock();
        $this->config = new Config(new EntityConfigId('ownership', self::SOME_CLASS));
    }

    public function testClearCacheForClassName(): void
    {
        $this->cache->expects(self::once())
            ->method('delete')
            ->with(self::SOME_CLASS);

        $this->provider->clearCache(self::SOME_CLASS);
    }

    public function testClearCacheForEntityProxyClassName(): void
    {
        $this->cache->expects(self::once())
            ->method('delete')
            ->with(self::SOME_CLASS);

        $this->provider->clearCache('\\' . Proxy::MARKER . '\\' . self::SOME_CLASS);
    }

    public function testClearCacheAll(): void
    {
        $this->cache->expects(self::once())
            ->method('clear');

        $this->provider->clearCache();
    }

    public function testGetMetadataWithoutCache(): void
    {
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::SOME_CLASS)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('ownership', self::SOME_CLASS)
            ->willReturn($this->config);

        $this->cache->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($cacheKey, $callback) {
                return $callback($this->createMock(ItemInterface::class));
            });

        self::assertEquals(new OwnershipMetadata(), $this->provider->getMetadata(self::SOME_CLASS));
    }

    public function testGetMetadataForEntityProxy(): void
    {
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::SOME_CLASS)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('ownership', self::SOME_CLASS)
            ->willReturn($this->config);

        $this->cache->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($cacheKey, $callback) {
                return $callback($this->createMock(ItemInterface::class));
            });

        self::assertEquals(
            new OwnershipMetadata(),
            $this->provider->getMetadata('\\' . Proxy::MARKER . '\\' . self::SOME_CLASS)
        );
    }

    public function testGetMetadataForNull(): void
    {
        $this->configManager->expects(self::never())
            ->method($this->anything());

        $this->cache->expects(self::never())
            ->method($this->anything());

        $metadata = new OwnershipMetadata();
        self::assertEquals($metadata, $this->provider->getMetadata(null));
    }

    public function testGetMetadataUndefinedClassWithCache(): void
    {
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::UNDEFINED_CLASS)
            ->willReturn(false);
        $this->configManager->expects(self::never())
            ->method('getEntityConfig');
        $this->cache->expects(self::exactly(2))
            ->method('get')
            ->with(self::UNDEFINED_CLASS)
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function ($cacheKey, $callback) {
                    return $callback($this->createMock(ItemInterface::class));
                }),
                true
            );

        $metadata = new OwnershipMetadata();
        $providerWithCleanCache = clone $this->provider;

        // no cache
        self::assertEquals($metadata, $this->provider->getMetadata(self::UNDEFINED_CLASS));
        // local cache
        self::assertEquals($metadata, $this->provider->getMetadata(self::UNDEFINED_CLASS));
        // cache
        self::assertEquals($metadata, $providerWithCleanCache->getMetadata(self::UNDEFINED_CLASS));
    }

    public function testWarmUpCacheWithoutClassName(): void
    {
        $configs = [$this->config];

        $this->configManager->expects(self::once())
            ->method('getConfigs')
            ->willReturn($configs);
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::SOME_CLASS)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('ownership', self::SOME_CLASS)
            ->willReturn($this->config);
        $this->cache->expects(self::once())
            ->method('get')
            ->with(self::SOME_CLASS)
            ->willReturnCallback(function ($cacheKey, $callback) {
                return $callback($this->createMock(ItemInterface::class));
            });

        $this->provider->warmUpCache();
    }

    public function testWarmUpCacheWithClassName(): void
    {
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::SOME_CLASS)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('ownership', self::SOME_CLASS)
            ->willReturn($this->config);
        $this->cache->expects(self::once())
            ->method('get')
            ->with(self::SOME_CLASS)
            ->willReturnCallback(function ($cacheKey, $callback) {
                return $callback($this->createMock(ItemInterface::class));
            });

        $this->provider->warmUpCache(self::SOME_CLASS);
    }

    public function testWarmUpCacheWithEntityProxyClassName(): void
    {
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::SOME_CLASS)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('ownership', self::SOME_CLASS)
            ->willReturn($this->config);
        $this->cache->expects(self::once())
            ->method('get')
            ->with(self::SOME_CLASS)
            ->willReturnCallback(function ($cacheKey, $callback) {
                return $callback($this->createMock(ItemInterface::class));
            });

        $this->provider->warmUpCache('\\' . Proxy::MARKER . '\\' . self::SOME_CLASS);
    }
}
