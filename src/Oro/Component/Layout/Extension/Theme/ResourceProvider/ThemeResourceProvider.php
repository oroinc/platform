<?php

namespace Oro\Component\Layout\Extension\Theme\ResourceProvider;

use Oro\Component\Config\Cache\PhpArrayConfigProvider;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\ResourcesContainerInterface;
use Oro\Component\Layout\BlockViewCache;
use Oro\Component\Layout\Config\Loader\LayoutUpdateCumulativeResourceLoader;
use Oro\Component\Layout\Loader\LayoutUpdateLoaderInterface;
use Symfony\Component\Config\ConfigCacheFactoryInterface;

/**
 * The provider for layout theme resources
 * that are loaded from "Resources/views/layouts" directories.
 */
class ThemeResourceProvider extends PhpArrayConfigProvider implements ResourceProviderInterface
{
    /** @var LastModificationDateProvider */
    private $lastModificationDateProvider;

    /** @var LayoutUpdateLoaderInterface */
    private $loader;

    /** @var array */
    private $excludedPaths;

    /** @var BlockViewCache */
    private $blockViewCache;

    /**
     * @param string                      $cacheFile
     * @param ConfigCacheFactoryInterface $configCacheFactory
     * @param LastModificationDateProvider $lastModificationDateProvider
     * @param LayoutUpdateLoaderInterface $loader
     * @param BlockViewCache              $blockViewCache
     * @param string[]                    $excludedPaths
     */
    public function __construct(
        string $cacheFile,
        ConfigCacheFactoryInterface $configCacheFactory,
        LastModificationDateProvider $lastModificationDateProvider,
        LayoutUpdateLoaderInterface $loader,
        BlockViewCache $blockViewCache,
        array $excludedPaths = []
    ) {
        parent::__construct($cacheFile, $configCacheFactory);
        $this->lastModificationDateProvider = $lastModificationDateProvider;
        $this->loader = $loader;
        $this->excludedPaths = $excludedPaths;
        $this->blockViewCache = $blockViewCache;
    }

    /**
     * {@inheritdoc}
     */
    public function getResources(): array
    {
        return $this->doGetConfig();
    }

    /**
     * {@inheritdoc}
     */
    public function findApplicableResources(array $paths): array
    {
        $values = [];
        $resources = $this->getResources();
        foreach ($paths as $path) {
            $value = $resources;
            $pathElements = \explode(DIRECTORY_SEPARATOR, $path);
            foreach ($pathElements as $pathElement) {
                $value = $this->readValue($value, $pathElement);
                if (null === $value) {
                    break;
                }
            }

            if ($value && \is_array($value)) {
                $values[] = \array_filter($value, '\is_string');
            }
        }
        if (!empty($values)) {
            $values = \array_merge(...$values);
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    protected function doLoadConfig(ResourcesContainerInterface $resourcesContainer)
    {
        $themeLayoutUpdates = $this->loadThemeLayoutUpdates($resourcesContainer);

        $this->lastModificationDateProvider->updateLastModificationDate(
            new \DateTime('now', new \DateTimeZone('UTC'))
        );

        $this->blockViewCache->reset();

        return $themeLayoutUpdates;
    }

    /**
     * @param ResourcesContainerInterface $resourcesContainer
     *
     * @return array
     */
    private function loadThemeLayoutUpdates(ResourcesContainerInterface $resourcesContainer)
    {
        $themeLayoutUpdates = [];
        $configLoader = new CumulativeConfigLoader(
            'oro_layout_updates_list',
            new LayoutUpdateCumulativeResourceLoader(
                'Resources/views/layouts/',
                -1,
                false,
                $this->loader->getUpdateFileNamePatterns()
            )
        );
        $resources = $configLoader->load($resourcesContainer);
        foreach ($resources as $resource) {
            /**
             * $resource->data contains data in following format
             * [
             *    'directory-where-updates-found' => [
             *       'found update absolute filename',
             *       ...
             *    ]
             * ]
             */
            $resourceThemeLayoutUpdates = $this->filterThemeLayoutUpdates($this->excludedPaths, $resource->data);
            $resourceThemeLayoutUpdates = $this->sortThemeLayoutUpdates($resourceThemeLayoutUpdates);

            $themeLayoutUpdates = \array_merge_recursive($themeLayoutUpdates, $resourceThemeLayoutUpdates);
        }

        return $themeLayoutUpdates;
    }

    /**
     * @param array $existThemePaths
     * @param array $themes
     *
     * @return array
     */
    private function filterThemeLayoutUpdates(array $existThemePaths, array $themes)
    {
        foreach ($themes as $theme => $themePaths) {
            foreach ($themePaths as $pathIndex => $path) {
                if (\is_string($path) && isset($existThemePaths[$path])) {
                    unset($themePaths[$pathIndex]);
                }
            }
            if (empty($themePaths)) {
                unset($themes[$theme]);
            } else {
                $themes[$theme] = $themePaths;
            }
        }

        return $themes;
    }


    /**
     * @param array $updates
     *
     * @return array
     */
    private function sortThemeLayoutUpdates(array $updates)
    {
        $directories = [];
        $files = [];
        foreach ($updates as $key => $update) {
            if (\is_array($update)) {
                $update = $this->sortThemeLayoutUpdates($update);
                $directories[$key] = $update;
            } else {
                $files[] = $update;
            }
        }

        \sort($files);
        \ksort($directories);
        $updates = \array_merge($files, $directories);

        return $updates;
    }

    /**
     * @param mixed  $array
     * @param string $property
     *
     * @return array|null
     */
    private function readValue($array, $property)
    {
        if (\is_array($array) && isset($array[$property])) {
            return $array[$property];
        }

        return null;
    }
}
