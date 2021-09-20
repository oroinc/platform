<?php

namespace Oro\Bundle\AssetBundle\Cache;

use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
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

    private ThemeManager $themeManager;

    public function __construct(
        KernelInterface $kernel,
        array $webpackDevServerOptions,
        ThemeManager $themeManager
    ) {
        $this->kernel = $kernel;
        $this->webpackDevServerOptions = $webpackDevServerOptions;
        $this->themeManager = $themeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $config['paths'] = $this->getBundlesPath();
        $config['devServerOptions'] = $this->webpackDevServerOptions;

        $config['themes'] = array_keys($this->themeManager->getEnabledThemes());

        @file_put_contents($this->getFilePath($cacheDir), \json_encode($config, JSON_UNESCAPED_SLASHES));
    }

    public function exists(string $cacheDir): bool
    {
        return file_exists($this->getFilePath($cacheDir));
    }

    private function getFilePath(string $cacheDir): string
    {
        return $cacheDir.'/asset-config.json';
    }

    private function getBundlesPath(): array
    {
        $paths = [];
        foreach ($this->kernel->getBundles() as $bundle) {
            $paths[] = $bundle->getPath();
        }

        return $paths;
    }
}
