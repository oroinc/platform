<?php

namespace Oro\Bundle\AssetBundle\Tests\Unit\Cache;

use Oro\Bundle\AssetBundle\Cache\AssetConfigCache;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class AssetConfigCacheTest extends TestCase
{
    use TempDirExtension;

    const WEBPACK_DEV_SERVER_OPTIONS = ['webpack_dev_server_options'];

    public function testWarmUp(): void
    {
        $bundles = [
            $this->createBundleMock('first/bundle/path'),
            $this->createBundleMock('second/bundle/path'),
        ];
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects($this->once())
            ->method('getBundles')
            ->willReturn($bundles);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('getParameter')
            ->with('assets_version')
            ->willReturn('testAssetVersion');
        $kernel->expects($this->once())
            ->method('getContainer')
            ->willReturn($container);

        $manager = $this->getThemeManagerMock();
        $warmer = new AssetConfigCache($kernel, self::WEBPACK_DEV_SERVER_OPTIONS);
        $warmer->setThemeManager($manager);
        $tempDir = $this->getTempDir('cache');
        $warmer->warmUp($tempDir);
        $file = $tempDir.'/asset-config.json';
        $this->assertJsonFileEqualsJsonFile(__DIR__.'/asset-config.json', $file);
    }

    /**
     * @param string $path
     * @return BundleInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createBundleMock(string $path): BundleInterface
    {
        $bundle = $this->createMock(BundleInterface::class);
        $bundle->expects($this->once())
            ->method('getPath')
            ->willReturn($path);

        return $bundle;
    }

    protected function getThemeManagerMock(): ThemeManager
    {
        $manager = $this->createMock(ThemeManager::class);
        $manager->method('getEnabledThemes')
            ->willReturn([]);

        return $manager;
    }
}
