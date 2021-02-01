<?php

namespace Oro\Bundle\AssetBundle\Cache;

use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Update cache of bundles path and webpack dev server options at asset-config.json, that used by webpack asset builder.
 */
class AssetConfigCache implements WarmableInterface
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var array
     */
    private $webpackDevServerOptions;

    /**
     * @param KernelInterface $kernel
     * @param array           $webpackDevServerOptions
     */
    public function __construct(
        KernelInterface $kernel,
        array $webpackDevServerOptions
    ) {
        $this->kernel = $kernel;
        $this->webpackDevServerOptions = $webpackDevServerOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $config['paths'] = $this->getBundlesPath();
        $config['devServerOptions'] = $this->webpackDevServerOptions;
        $config['assetVersion'] = $this->kernel->getContainer()->getParameter('assets_version');

        @file_put_contents($this->getFilePath($cacheDir), \json_encode($config, JSON_UNESCAPED_SLASHES));
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
}
