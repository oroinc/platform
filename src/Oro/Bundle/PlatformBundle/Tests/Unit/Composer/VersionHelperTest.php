<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Composer;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\PlatformBundle\Composer\VersionHelper;
use Oro\Bundle\PlatformBundle\OroPlatformBundle;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class VersionHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testGetVersion()
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('get')
            ->with(UniversalCacheKeyGenerator::normalizeCacheKey(OroPlatformBundle::PACKAGE_NAME))
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $helper = new VersionHelper($cache);

        $version = $helper->getVersion();
        // Check that local cache used
        $this->assertEquals($version, $version);
    }

    public function testGetVersionNotAvailable()
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('get')
            ->with(UniversalCacheKeyGenerator::normalizeCacheKey('non-' . OroPlatformBundle::PACKAGE_NAME))
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });
        $helper = new VersionHelper($cache);

        $this->assertEquals('N/A', $helper->getVersion('non-' . OroPlatformBundle::PACKAGE_NAME));
    }

    public function testGetVersionCached()
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('get')
            ->with(UniversalCacheKeyGenerator::normalizeCacheKey(OroPlatformBundle::PACKAGE_NAME))
            ->willReturn('1.0');

        $helper = new VersionHelper($cache);

        $this->assertEquals('1.0', $helper->getVersion());
    }
}
