<?php

namespace Oro\Bundle\DataGridBundle\Provider;

use Oro\Bundle\DataGridBundle\Provider\Cache\GridCacheUtils;
use Oro\Component\Config\Cache\ConfigCache;
use Oro\Component\Config\Cache\PhpConfigCacheAccessor;
use Oro\Component\Config\Cache\WarmableConfigCacheInterface;
use Oro\Component\Config\Loader\Factory\CumulativeConfigLoaderFactory;
use Oro\Component\Config\ResourcesContainer;
use Oro\Component\Config\ResourcesContainerInterface;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\Filesystem\Filesystem;

/**
 * The provider for datagrids configuration
 * that is loaded from "Resources/config/oro/datagrids.yml" files
 * and not processed by SystemAwareResolver.
 */
class RawConfigurationProvider implements RawConfigurationProviderInterface, WarmableConfigCacheInterface
{
    private const CONFIG_FILE = 'Resources/config/oro/datagrids.yml';
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

    private GridCacheUtils $gridCacheUtils;

    public function __construct(string $cacheDir, bool $debug, GridCacheUtils $gridCacheUtils)
    {
        $this->cacheDir = $cacheDir;
        $this->debug = $debug;
        $this->gridCacheUtils = $gridCacheUtils;
    }

    #[\Override]
    public function getRawConfiguration(string $gridName): ?array
    {
        $this->ensureRawConfigurationLoaded($gridName);

        return $this->rawConfiguration[$gridName] ?? null;
    }

    #[\Override]
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
     * Group grid config with their mixins and save to cache
     *
     * @param array $configs [grid name => config, ...]
     *
     * @return array [grid name => [grid name => config, mixin grid name => config, ...], ...]
     */
    public function aggregateConfiguration(array $configs): array
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
                    $gridCacheAccessor->save($this->gridCacheUtils->getGridConfigCache($gridName), $gridConfigs);
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
            $gridCache = $this->gridCacheUtils->getGridConfigCache($gridName);
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
            if (isset($resource->data[RawConfigurationProviderInterface::ROOT_SECTION])) {
                $grids = $resource->data[RawConfigurationProviderInterface::ROOT_SECTION];
                if (\is_array($grids)) {
                    $configs = ArrayUtil::arrayMergeRecursiveDistinct($configs, $grids);
                }
            }
        }

        return $this->aggregateConfiguration($configs);
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
}
