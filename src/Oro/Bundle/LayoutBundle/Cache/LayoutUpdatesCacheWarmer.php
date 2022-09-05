<?php

namespace Oro\Bundle\LayoutBundle\Cache;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\ExpressionLanguage\ExpressionLanguageCacheWarmer;
use Oro\Component\Layout\LayoutFactoryBuilderInterface;
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
    private LayoutFactoryBuilderInterface $layoutFactoryBuilder;
    private ExpressionLanguageCacheWarmer $expressionLanguageCacheWarmer;

    public function __construct(
        LayoutUpdateLoader $layoutUpdateLoader,
        KernelInterface $kernel,
        LayoutFactoryBuilderInterface $layoutFactoryBuilder,
        ExpressionLanguageCacheWarmer $expressionLanguageCacheWarmer
    ) {
        $this->layoutUpdateLoader = $layoutUpdateLoader;
        $this->kernel = $kernel;
        $this->layoutFactoryBuilder = $layoutFactoryBuilder;
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
        $this->collectExpressionsFromBlockTypes();
        $this->expressionLanguageCacheWarmer->write();
    }

    private function loadLayoutUpdates(): void
    {
        foreach ($this->kernel->getBundles() as $bundle) {
            $this->loadLayoutsUpdatesFromDir($bundle->getPath() . '/Resources/views/layouts');
        }
        $this->loadLayoutsUpdatesFromDir($this->kernel->getProjectDir() . '/templates/layouts');
    }

    private function loadLayoutsUpdatesFromDir(string $layoutsDir): void
    {
        if (!is_dir($layoutsDir)) {
            return;
        }

        $finder = new Finder();
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

    private function collectExpressionsFromBlockTypes(): void
    {
        $layoutRegistry = $this->layoutFactoryBuilder->getLayoutFactory()->getRegistry();
        $typeNames = $layoutRegistry->getTypeNames();
        foreach ($typeNames as $typeName) {
            $type = $layoutRegistry->getType($typeName);
            $optionsResolver = new OptionsResolver();
            $type->configureOptions($optionsResolver);
            $layoutRegistry->configureOptions($typeName, $optionsResolver);
            $defaultOptions = $optionsResolver->getDefaults();
            foreach ($defaultOptions as $value) {
                if (\is_string($value) && $value && '=' === $value[0]) {
                    $this->expressionLanguageCacheWarmer->collect(substr($value, 1));
                }
            }
        }
    }
}
