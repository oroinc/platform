<?php

namespace Oro\Component\Layout\Extension\Theme\ResourceProvider;

use Doctrine\Common\Cache\Cache;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Layout\BlockViewCache;
use Oro\Component\Layout\Config\Loader\LayoutUpdateCumulativeResourceLoader;
use Oro\Component\Layout\Loader\LayoutUpdateLoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * The provider of layout theme resources.
 */
class ThemeResourceProvider implements ResourceProviderInterface
{
    const CACHE_KEY = 'oro_layout.theme_updates_resources';
    const CACHE_LAST_MODIFICATION_DATE = 'oro_layout.last_modification_date';

    /** @var LayoutUpdateLoaderInterface */
    private $loader;

    /** @var array */
    private $resources = [];

    /** @var array */
    private $excludedPaths = [];

    /** @var Cache */
    private $cache;

    /** @var BlockViewCache */
    private $blockViewCache;

    /**
     * @param LayoutUpdateLoaderInterface $loader
     * @param BlockViewCache $blockViewCache
     * @param array $excludedPaths
     */
    public function __construct(
        LayoutUpdateLoaderInterface $loader,
        BlockViewCache $blockViewCache,
        array $excludedPaths = []
    ) {
        $this->loader = $loader;
        $this->excludedPaths = $excludedPaths;
        $this->blockViewCache = $blockViewCache;
    }

    /**
     * @param Cache $cache
     *
     * @return ThemeResourceProvider
     */
    public function setCache(Cache $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getResources()
    {
        if (!$this->resources) {
            $resources = false;
            if (null !== $this->cache) {
                $resources = $this->cache->fetch(self::CACHE_KEY);
            }
            if (false === $resources) {
                $this->loadResources();
            } else {
                $this->resources = $resources;
            }
        }

        return $this->resources;
    }

    /**
     * {@inheritdoc}
     */
    public function loadResources(ContainerBuilder $container = null, array $resources = [])
    {
        $resources = array_merge($resources, $this->getConfigLoader()->load($container));
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

            $this->resources = array_merge_recursive($this->resources, $resourceThemeLayoutUpdates);
        }

        if ($this->cache instanceof Cache) {
            $this->cache->save(self::CACHE_KEY, $this->resources);

            $now = new \DateTime('now', new \DateTimeZone('UTC'));
            $this->cache->save(self::CACHE_LAST_MODIFICATION_DATE, $now);

            $this->blockViewCache->reset();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findApplicableResources(array $paths)
    {
        $result = [];

        foreach ($paths as $path) {
            $pathArray = explode(DIRECTORY_SEPARATOR, $path);

            $value = $this->getResources();
            for ($i = 0, $length = count($pathArray); $i < $length; ++$i) {
                $value = $this->readValue($value, $pathArray[$i]);

                if (null === $value) {
                    break;
                }
            }

            if ($value && is_array($value)) {
                $result = array_merge($result, array_filter($value, 'is_string'));
            }
        }

        return $result;
    }

    /**
     * @return CumulativeConfigLoader
     */
    private function getConfigLoader()
    {
        $filenamePatterns = $this->loader->getUpdateFileNamePatterns();
        $configLoader = new CumulativeConfigLoader(
            'oro_layout_updates_list',
            [new LayoutUpdateCumulativeResourceLoader('Resources/views/layouts/', -1, false, $filenamePatterns)]
        );

        return $configLoader;
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
                if (is_string($path) && isset($existThemePaths[$path])) {
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
            if (is_array($update)) {
                $update = $this->sortThemeLayoutUpdates($update);
                $directories[$key] = $update;
            } else {
                $files[] = $update;
            }
        }

        sort($files);
        ksort($directories);
        $updates = array_merge($files, $directories);

        return $updates;
    }

    /**
     * @param array $array
     * @param string $property
     *
     * @return array|null
     */
    private function readValue($array, $property)
    {
        if (is_array($array) && isset($array[$property])) {
            return $array[$property];
        }

        return null;
    }
}
