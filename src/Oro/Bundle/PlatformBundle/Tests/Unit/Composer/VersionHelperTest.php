<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Composer;

use Composer\Package\PackageInterface;
use Composer\Repository\WritableRepositoryInterface;
use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\PlatformBundle\Composer\LocalRepositoryFactory;
use Oro\Bundle\PlatformBundle\Composer\VersionHelper;
use Oro\Bundle\PlatformBundle\OroPlatformBundle;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class VersionHelperTest extends \PHPUnit\Framework\TestCase
{
    private const VERSION = '1.0';

    /** @var LocalRepositoryFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $factory;

    /** @var WritableRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $repo;

    protected function setUp(): void
    {
        $this->factory = $this->createMock(LocalRepositoryFactory::class);
        $this->repo = $this->createMock(WritableRepositoryInterface::class);
        $this->factory->expects($this->any())
            ->method('getLocalRepository')
            ->willReturn($this->repo);
    }

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

        $helper = new VersionHelper($this->factory, $cache);

        $package = $this->createMock(PackageInterface::class);
        $package->expects($this->once())
            ->method('getPrettyVersion')
            ->willReturn(self::VERSION);
        $this->repo->expects($this->once())
            ->method('findPackages')
            ->willReturn([$package]);

        $this->assertEquals(self::VERSION, $helper->getVersion());
        // Check that local cache used
        $this->assertEquals(self::VERSION, $helper->getVersion());
    }

    public function testGetVersionNotAvailable()
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('get')
            ->with(UniversalCacheKeyGenerator::normalizeCacheKey(OroPlatformBundle::PACKAGE_NAME))
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });
        $helper = new VersionHelper($this->factory, $cache);

        $this->repo->expects($this->once())
            ->method('findPackages')
            ->willReturn([]);

        $this->assertEquals('N/A', $helper->getVersion());
    }

    public function testGetVersionCached()
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('get')
            ->with(UniversalCacheKeyGenerator::normalizeCacheKey(OroPlatformBundle::PACKAGE_NAME))
            ->willReturn(self::VERSION);

        $helper = new VersionHelper($this->factory, $cache);

        $this->assertEquals(self::VERSION, $helper->getVersion());
    }
}
