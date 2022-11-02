<?php

namespace Oro\Component\Layout\Extension\Theme\ResourceProvider;

use Oro\Component\Config\Cache\PhpArrayConfigProvider;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\FolderContentCumulativeLoader;
use Oro\Component\Config\ResourcesContainerInterface;
use Oro\Component\Layout\BlockViewCache;
use Oro\Component\Layout\Loader\LayoutUpdateLoaderInterface;

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

    /** @var BlockViewCache */
    private $blockViewCache;

    /** @var string[] */
    private $excludeFilePathPatterns;

    /** @var string[] */
    private $additionalResourcePaths;

    /**
     * @param string                       $cacheFile
     * @param bool                         $debug
     * @param LastModificationDateProvider $lastModificationDateProvider
     * @param LayoutUpdateLoaderInterface  $loader
     * @param BlockViewCache               $blockViewCache
     * @param string[]                     $excludeFilePathPatterns
     * @param string[]                     $additionalResourcePaths
     */
    public function __construct(
        string $cacheFile,
        bool $debug,
        LastModificationDateProvider $lastModificationDateProvider,
        LayoutUpdateLoaderInterface $loader,
        BlockViewCache $blockViewCache,
        array $excludeFilePathPatterns = [],
        array $additionalResourcePaths = []
    ) {
        parent::__construct($cacheFile, $debug);
        $this->lastModificationDateProvider = $lastModificationDateProvider;
        $this->loader = $loader;
        $this->blockViewCache = $blockViewCache;
        $this->excludeFilePathPatterns = $excludeFilePathPatterns;
        $this->additionalResourcePaths = $additionalResourcePaths;
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
            $this->getThemeLayoutUpdatesPathsLoaders()
        );
        $resources = $configLoader->load($resourcesContainer);
        foreach ($resources as $resource) {
            $themeLayoutUpdates[] = $this->sortThemeLayoutUpdates($resource->data);
        }

        return \array_merge_recursive(...$themeLayoutUpdates);
    }

    /**
     * @return FolderContentCumulativeLoader[]
     */
    private function getThemeLayoutUpdatesPathsLoaders()
    {
        $paths = array_merge(['Resources/views/layouts/'], $this->additionalResourcePaths);
        $loaders = [];
        foreach ($paths as $path) {
            $loaders[] = new FolderContentCumulativeLoader(
                $path,
                -1,
                false,
                new LayoutUpdateFileMatcher(
                    $this->loader->getUpdateFileNamePatterns(),
                    $this->excludeFilePathPatterns
                )
            );
        }

        return $loaders;
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
