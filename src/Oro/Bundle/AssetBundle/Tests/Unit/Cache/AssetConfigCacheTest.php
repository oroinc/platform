<?php

namespace Oro\Bundle\AssetBundle\Tests\Unit\Cache;

use Doctrine\DBAL\DBALException;
use Oro\Bundle\AssetBundle\Cache\AssetConfigCache;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class AssetConfigCacheTest extends TestCase
{
    use TempDirExtension;

    public function testWarmUp(): void
    {
        /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager */
        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->once())
            ->method('get')
            ->with('oro_ui.application_url')
            ->willReturn('/application/url');

        $container = $this->createContainerMock($configManager);
        $kernel = $this->createKernelMock($container);

        $warmer = new AssetConfigCache($kernel);
        $tempDir = $this->getTempDir('cache');
        $warmer->warmUp($tempDir);
        $file = $tempDir.'/asset-config.json';
        $this->assertJsonFileEqualsJsonFile(__DIR__.'/asset-config-installed.json', $file);
    }

    public function testWarmUpNotInstalled(): void
    {
        /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager */
        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->once())
            ->method('get')
            ->with('oro_ui.application_url')
            ->willThrowException(new DBALException());

        $container = $this->createContainerMock($configManager);
        $kernel = $this->createKernelMock($container);

        $warmer = new AssetConfigCache($kernel);
        $tempDir = $this->getTempDir('cache');
        $warmer->warmUp($tempDir);
        $file = $tempDir.'/asset-config.json';
        $this->assertJsonFileEqualsJsonFile(__DIR__.'/asset-config-not-installed.json', $file);
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

    /**
     * @param ConfigManager $configManager
     * @return ContainerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createContainerMock(ConfigManager $configManager): ContainerInterface
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with('oro_config.global')
            ->willReturn($configManager);

        return $container;
    }

    /**
     * @param ContainerInterface $container
     * @return KernelInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createKernelMock(ContainerInterface $container): KernelInterface
    {
        $bundles = [
            $this->createBundleMock('first/bundle/path'),
            $this->createBundleMock('second/bundle/path'),
        ];
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects($this->once())
            ->method('getBundles')
            ->willReturn($bundles);
        $kernel->expects($this->once())
            ->method('getContainer')
            ->willReturn($container);

        return $kernel;
    }
}
