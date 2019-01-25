<?php

namespace Oro\Bundle\AssetBundle\Cache;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Update cache of bundles path and application_url at asset-config.json, that used by webpack asset builder.
 */
class AssetConfigCache implements WarmableInterface
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $config['paths'] = $this->getBundlesPath();
        $config['applicationUrl'] = $this->getApplicationUrl();

        @file_put_contents($this->getFilePath($cacheDir), \json_encode($config));
    }

    /**
     * @param string $cacheDir
     * @return bool
     */
    public function exists(string $cacheDir): bool
    {
        return file_exists($this->getFilePath($cacheDir));
    }

    /**
     * @param string $cacheDir
     * @return string
     */
    private function getFilePath(string $cacheDir): string
    {
        return $cacheDir.'/asset-config.json';
    }

    /**
     * @return array
     */
    private function getBundlesPath(): array
    {
        $paths = [];
        foreach ($this->kernel->getBundles() as $bundle) {
            $paths[] = $bundle->getPath();
        }

        return $paths;
    }

    /**
     * @return string
     */
    private function getApplicationUrl(): string
    {
        /** @var ConfigManager $configManager */
        $configManager = $this->kernel->getContainer()->get('oro_config.global');
        try {
            $applicationUrl = $configManager->get('oro_ui.application_url');
        } catch (\Exception $exception) {
            /** We should not use configuration settings when an application is not installed */
            $applicationUrl = '/';
        }

        return $applicationUrl;
    }
}
