<?php

namespace Oro\Bundle\LayoutBundle\Cache;

use Oro\Component\Layout\Loader\LayoutUpdateLoader;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Generates PHP classes in a cache for all the layout update YAML files.
 */
class LayoutUpdatesCacheWarmer implements CacheWarmerInterface
{
    /**
     * @var LayoutUpdateLoader
     */
    private $layoutUpdateLoader;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @param LayoutUpdateLoader $layoutUpdateLoader
     * @param KernelInterface    $kernel
     */
    public function __construct(LayoutUpdateLoader $layoutUpdateLoader, KernelInterface $kernel)
    {
        $this->layoutUpdateLoader = $layoutUpdateLoader;
        $this->kernel = $kernel;
    }

    /**
     * @inheritDoc
     */
    public function isOptional()
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function warmUp($cacheDir)
    {
        foreach ($this->kernel->getBundles() as $bundle) {
            $finder = new Finder();
            $layoutsDir = $bundle->getPath().'/Resources/views/layouts';
            if (!is_dir($layoutsDir)) {
                continue;
            }

            $finder
                ->in($layoutsDir)
                ->exclude('config')
                ->files()
                ->name('*.yml')
                ->notName('theme.yml');

            foreach ($finder as $file) {
                $layoutUpdatePath = $file->getRealPath();
                $this->layoutUpdateLoader->load($layoutUpdatePath);
            }
        }
    }
}
