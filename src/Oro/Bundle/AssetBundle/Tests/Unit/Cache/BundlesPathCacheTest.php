<?php

namespace Oro\Bundle\AssetBundle\Tests\Unit\Cache;

use Oro\Bundle\AssetBundle\Cache\BundlesPathCache;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class BundlesPathCacheTest extends TestCase
{
    use TempDirExtension;

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

        $warmer = new BundlesPathCache($kernel);
        $tempDir = $this->getTempDir('cache');
        $warmer->warmUp($tempDir);
        $file = $tempDir.'/bundles.json';
        $this->assertJsonFileEqualsJsonFile(__DIR__.'/bundles.json', $file);
    }

    /**
     * @param string $path
     * @return BundleInterface
     */
    protected function createBundleMock(string $path): BundleInterface
    {
        $bundle = $this->createMock(BundleInterface::class);
        $bundle->expects($this->once())
            ->method('getPath')
            ->willReturn($path);

        return $bundle;
    }
}
