<?php

namespace Oro\Bundle\DataGridBundle\Provider;

use Oro\Component\Config\Cache\ConfigCache;
use Oro\Component\Config\Cache\PhpConfigCacheAccessor;
use Oro\Component\Config\Cache\WarmableConfigCacheInterface;
use Oro\Component\Config\Loader\Factory\CumulativeConfigLoaderFactory;
use Oro\Component\Config\ResourcesContainer;
use Oro\Component\Config\ResourcesContainerInterface;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Config\ResourceCheckerConfigCache;
use Symfony\Component\Filesystem\Filesystem;

/**
 * The provider for datagrids configuration
 * that is loaded from "Resources/config/oro/datagrids.yml" files
 * and not processed by SystemAwareResolver.
 */
class RawConfigurationProvider implements WarmableConfigCacheInterface
{
    private const CONFIG_FILE = 'Resources/config/oro/datagrids.yml';
    private const ROOT_SECTION = 'datagrids';
    private const MIXINS_SECTION = 'mixins';

    /** @var string */
    private $cacheDir;

    /** @var bool */
    private $debug;

    /** @var PhpConfigCacheAccessor|null */
    private $rootCacheAccessor;

    /** @var PhpConfigCacheAccessor|null */
    private $gridCacheAccessor;

    /** @var bool */
    private $hasCache = false;

    /** @var array */
    private $rawConfiguration = [];

    /** @var int */
    private $cacheDirLength;

    public function __construct(string $cacheDir, bool $debug)
    {
        $this->cacheDir = $cacheDir;
        $this->debug = $debug;
        $this->cacheDirLength = \strlen($this->cacheDir);
    }

    public function getRawConfiguration(string $gridName): ?array
    {
        $this->ensureRawConfigurationLoaded($gridName);

        return $this->rawConfiguration[$gridName] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUpCache(): void
    {
        $this->hasCache = false;
        if (\is_dir($this->cacheDir)) {
            $fs = new Filesystem();
            $fs->remove($this->cacheDir);
        }
        $this->ensureCacheWarmedUp();
    }

    /**
     * Makes sure that configuration cache was warmed up.
     */
    private function ensureCacheWarmedUp(): void
    {
        if (!$this->hasCache) {
            $rootCache = new ConfigCache($this->cacheDir . '/datagrids.php', $this->debug);
            if (!$rootCache->isFresh()) {
                $resourcesContainer = new ResourcesContainer();
                $gridCacheAccessor = $this->getGridCacheAccessor();
                $aggregatedConfigs = $this->loadConfiguration($resourcesContainer);
                foreach ($aggregatedConfigs as $gridName => $gridConfigs) {
                    $gridCacheAccessor->save($this->getGridConfigCache($gridName), $gridConfigs);
                }
                $this->getRootCacheAccessor()->save($rootCache, true, $resourcesContainer->getResources());
            }

            $this->hasCache = $this->getRootCacheAccessor()->load($rootCache);
        }
    }

    private function ensureRawConfigurationLoaded(string $gridName): void
    {
        $this->ensureCacheWarmedUp();
        if (!isset($this->rawConfiguration[$gridName])) {
            $gridCache = $this->getGridConfigCache($gridName);
            if (\is_file($gridCache->getPath())) {
                $this->rawConfiguration = \array_merge(
                    $this->rawConfiguration,
                    $this->getGridCacheAccessor()->load($gridCache)
                );
            }
        }
    }

    /**
     * @param ResourcesContainerInterface $resourcesContainer
     *
     * @return array [grid name => [grid name => config, mixin grid name => config, ...], ...]
     */
    private function loadConfiguration(ResourcesContainerInterface $resourcesContainer): array
    {
        $configs = [];
        $cumulativeConfigLoader = CumulativeConfigLoaderFactory::create('oro_datagrid', self::CONFIG_FILE);
        $resources = $cumulativeConfigLoader->load($resourcesContainer);
        foreach ($resources as $resource) {
            if (isset($resource->data[self::ROOT_SECTION])) {
                $grids = $resource->data[self::ROOT_SECTION];
                if (\is_array($grids)) {
                    $configs = ArrayUtil::arrayMergeRecursiveDistinct($configs, $grids);
                }
            }
        }

        return $this->aggregateConfiguration($configs);
    }

    /**
     * Group grid config with their mixins and save to cache
     *
     * @param array $configs [grid name => config, ...]
     *
     * @return array [grid name => [grid name => config, mixin grid name => config, ...], ...]
     */
    private function aggregateConfiguration(array $configs): array
    {
        $result = [];
        foreach ($configs as $gridName => $gridConfig) {
            $aggregatedConfig = [$gridName => $gridConfig];
            if (isset($gridConfig[self::MIXINS_SECTION])) {
                $mixins = $gridConfig[self::MIXINS_SECTION];
                if (\is_array($mixins)) {
                    foreach ($mixins as $mixin) {
                        $aggregatedConfig[$mixin] = $configs[$mixin];
                    }
                } elseif (\is_string($mixins)) {
                    $aggregatedConfig[$mixins] = $configs[$mixins];
                }
            }
            $result[$gridName] = $aggregatedConfig;
        }

        return $result;
    }

    private function getRootCacheAccessor(): PhpConfigCacheAccessor
    {
        if (null === $this->rootCacheAccessor) {
            $this->rootCacheAccessor = new PhpConfigCacheAccessor(function ($config) {
                if (true !== $config) {
                    throw new \LogicException('Expected boolean TRUE.');
                }
            });
        }

        return $this->rootCacheAccessor;
    }

    private function getGridCacheAccessor(): PhpConfigCacheAccessor
    {
        if (null === $this->gridCacheAccessor) {
            $this->gridCacheAccessor = new PhpConfigCacheAccessor(function ($config) {
                if (!\is_array($config)) {
                    throw new \LogicException('Expected an array.');
                }
            });
        }

        return $this->gridCacheAccessor;
    }

    private function getGridConfigCache(string $gridName): ConfigCacheInterface
    {
        return new ResourceCheckerConfigCache($this->getGridFile($gridName));
    }

    private function getGridFile(string $gridName): string
    {
        // This ensures that the filename does not contain invalid chars.
        $fileName = \preg_replace('#[^a-z0-9-_]#i', '-', $gridName);

        // This ensures that the filename is not too long.
        // Most filesystems have a limit of 255 chars for each path component.
        // On Windows the the whole path is limited to 260 chars (including terminating null char).
        $fileNameLength = \strlen($fileName) + 4; // 4 === strlen('.php')
        if ($fileNameLength > 255 || $this->cacheDirLength + $fileNameLength > 259) {
            $fileName = \hash('sha256', $gridName);
        }

        return \sprintf('%s/%s.php', $this->cacheDir, $fileName);
    }
}
