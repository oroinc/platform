<?php

namespace Oro\Bundle\LayoutBundle\Cache;

use Oro\Component\Layout\ExpressionLanguage\ExpressionLanguageCacheWarmer;
use Oro\Component\Layout\Loader\LayoutUpdateLoader;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Generates PHP classes in a cache for all the layout update YAML files.
 */
class LayoutUpdatesCacheWarmer implements CacheWarmerInterface
{
    private LayoutUpdateLoader $layoutUpdateLoader;
    private KernelInterface $kernel;
    private ExpressionLanguageCacheWarmer $expressionLanguageCacheWarmer;

    public function __construct(
        LayoutUpdateLoader $layoutUpdateLoader,
        KernelInterface $kernel
    ) {
        $this->layoutUpdateLoader = $layoutUpdateLoader;
        $this->kernel = $kernel;
    }

    public function setCacheWarmer(ExpressionLanguageCacheWarmer $expressionLanguageCacheWarmer)
    {
        $this->expressionLanguageCacheWarmer = $expressionLanguageCacheWarmer;
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
        $this->loadLayoutUpdates();
        $this->expressionLanguageCacheWarmer->write();
    }

    private function loadLayoutUpdates(): void
    {
        foreach ($this->kernel->getBundles() as $bundle) {
            $finder = new Finder();
            $layoutsDir = $bundle->getPath() . '/Resources/views/layouts';
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
