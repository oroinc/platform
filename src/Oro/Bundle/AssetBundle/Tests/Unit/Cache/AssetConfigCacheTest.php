<?php

namespace Oro\Bundle\AssetBundle\Tests\Unit\Cache;

use Oro\Bundle\AssetBundle\Cache\AssetConfigCache;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Testing\TempDirExtension;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class AssetConfigCacheTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    private const WEBPACK_DEV_SERVER_OPTIONS = ['webpack_dev_server_options'];

    public function testWarmUp(): void
    {
        $bundles = [
            $this->getBundle('first/bundle/path'),
            $this->getBundle('second/bundle/path'),
        ];
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects($this->once())
            ->method('getBundles')
            ->willReturn($bundles);

        $manager = $this->createMock(ThemeManager::class);
        $manager->expects($this->once())
            ->method('getEnabledThemes')
            ->willReturn([]);

        $warmer = new AssetConfigCache($kernel, self::WEBPACK_DEV_SERVER_OPTIONS, $manager);
        $tempDir = $this->getTempDir('cache');
        $warmer->warmUp($tempDir);
        $file = $tempDir.'/asset-config.json';
        $this->assertJsonFileEqualsJsonFile(__DIR__.'/asset-config.json', $file);
    }

    private function getBundle(string $path): BundleInterface
    {
        $bundle = $this->createMock(BundleInterface::class);
        $bundle->expects($this->once())
            ->method('getPath')
            ->willReturn($path);

        return $bundle;
    }
}
