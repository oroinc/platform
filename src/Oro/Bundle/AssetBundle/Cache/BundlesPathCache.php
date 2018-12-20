<?php

namespace Oro\Bundle\AssetBundle\Cache;

use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Update cache of bundles path at bundles.json, that used by webpack asset builder.
 */
class BundlesPathCache implements WarmableInterface
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
        $paths = [];
        foreach ($this->kernel->getBundles() as $bundle) {
            $paths[] = $bundle->getPath();
        }

        @file_put_contents($this->getFilePath($cacheDir), \json_encode($paths));
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
        return $cacheDir.'/bundles.json';
    }
}
