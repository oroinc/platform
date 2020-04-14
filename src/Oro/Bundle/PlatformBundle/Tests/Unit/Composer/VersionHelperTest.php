<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Composer;

use Composer\Package\PackageInterface;
use Composer\Repository\WritableRepositoryInterface;
use Doctrine\Common\Cache\Cache;
use Oro\Bundle\PlatformBundle\Composer\LocalRepositoryFactory;
use Oro\Bundle\PlatformBundle\Composer\VersionHelper;
use Oro\Bundle\PlatformBundle\OroPlatformBundle;

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

    /**
     * @dataProvider hasCacheDataProvider
     */
    public function testGetVersion($hasCache)
    {
        if ($hasCache) {
            $cache = $this->createMock(Cache::class);
            $cache->expects($this->once())
                ->method('fetch')
                ->with(OroPlatformBundle::PACKAGE_NAME)
                ->willReturn(false);
            $cache->expects($this->once())
                ->method('save')
                ->with(OroPlatformBundle::PACKAGE_NAME, self::VERSION);

            $helper = new VersionHelper($this->factory, $cache);
        } else {
            $helper = new VersionHelper($this->factory);
        }

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

    public function hasCacheDataProvider()
    {
        return [
            [false],
            [true]
        ];
    }

    public function testGetVersionNotAvailable()
    {
        $helper = new VersionHelper($this->factory);

        $this->repo->expects($this->once())
            ->method('findPackages')
            ->willReturn([]);

        $this->assertEquals('N/A', $helper->getVersion());
    }

    public function testGetVersionCached()
    {
        $cache = $this->createMock(Cache::class);
        $cache->expects($this->once())
            ->method('fetch')
            ->with(OroPlatformBundle::PACKAGE_NAME)
            ->willReturn(self::VERSION);
        $cache->expects($this->never())
            ->method('save');

        $helper = new VersionHelper($this->factory, $cache);

        $this->assertEquals(self::VERSION, $helper->getVersion());
    }
}
