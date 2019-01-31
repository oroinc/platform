<?php

namespace Oro\Bundle\DataGridBundle\Provider;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Exception\RuntimeException;
use Oro\Component\Config\Cache\PhpConfigCacheAccessor;
use Oro\Component\Config\Cache\WarmableConfigCacheInterface;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\Config\ResourcesContainer;
use Oro\Component\Config\ResourcesContainerInterface;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\Config\ConfigCacheFactoryInterface;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Config\ResourceCheckerConfigCache;
use Symfony\Component\Filesystem\Filesystem;

/**
 * The provider for datagrids configuration
 * that is loaded from "Resources/config/oro/datagrids.yml" files.
 */
class ConfigurationProvider implements ConfigurationProviderInterface, WarmableConfigCacheInterface
{
    private const CONFIG_FILE = 'Resources/config/oro/datagrids.yml';

    private const ROOT_SECTION   = 'datagrids';
    private const MIXINS_SECTION = 'mixins';

    /** @var string */
    private $cacheDir;

    /** @var ConfigCacheFactoryInterface */
    private $configCacheFactory;

    /** @var SystemAwareResolver */
    private $resolver;

    /** @var PhpConfigCacheAccessor */
    private $rootCacheAccessor;

    /** @var PhpConfigCacheAccessor */
    private $gridCacheAccessor;

    /** @var bool */
    private $hasCache = false;

    /** @var array */
    private $rawConfiguration = [];

    /** @var array */
    private $processedConfiguration = [];

    /** @var int */
    private $cacheDirLength;

    /**
     * @param string                      $cacheDir
     * @param ConfigCacheFactoryInterface $configCacheFactory
     * @param SystemAwareResolver         $resolver
     */
    public function __construct(
        string $cacheDir,
        ConfigCacheFactoryInterface $configCacheFactory,
        SystemAwareResolver $resolver
    ) {
        $this->cacheDir = $cacheDir;
        $this->configCacheFactory = $configCacheFactory;
        $this->resolver = $resolver;
        $this->cacheDirLength = \strlen($this->cacheDir);
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable($gridName)
    {
        $this->ensureRawConfigurationLoaded($gridName);

        return isset($this->rawConfiguration[$gridName]);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration($gridName)
    {
        if (!isset($this->processedConfiguration[$gridName])) {
            $this->processedConfiguration[$gridName] = $this->resolver->resolve(
                $gridName,
                $this->getRawConfiguration($gridName)
            );
        }

        return DatagridConfiguration::createNamed($gridName, $this->processedConfiguration[$gridName]);
    }

    /**
     * @param string $gridName
     *
     * @return array
     */
    public function getRawConfiguration(string $gridName): array
    {
        if (!$this->isApplicable($gridName)) {
            throw new RuntimeException(\sprintf(
                'A configuration for "%s" datagrid was not found.',
                $gridName
            ));
        }

        return $this->rawConfiguration[$gridName];
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
            $rootCacheFile = $this->cacheDir . '/datagrids.php';
            $cache = $this->configCacheFactory->cache($rootCacheFile, function (ConfigCacheInterface $cache) {
                $resourcesContainer = new ResourcesContainer();
                $gridCacheAccessor = $this->getGridCacheAccessor();
                $aggregatedConfigs = $this->loadConfiguration($resourcesContainer);
                foreach ($aggregatedConfigs as $gridName => $gridConfigs) {
                    $gridCacheAccessor->save($this->getGridConfigCache($gridName), $gridConfigs);
                }
                $this->getRootCacheAccessor()->save($cache, true, $resourcesContainer->getResources());
            });

            $this->hasCache = $this->getRootCacheAccessor()->load($cache);
        }
    }

    /**
     * @param string $gridName
     */
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
        $configLoader = new CumulativeConfigLoader(
            'oro_datagrid',
            new YamlCumulativeFileLoader(self::CONFIG_FILE)
        );
        $resources = $configLoader->load($resourcesContainer);
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

    /**
     * @return PhpConfigCacheAccessor
     */
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

    /**
     * @return PhpConfigCacheAccessor
     */
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

    /**
     * @param string $gridName
     *
     * @return ConfigCacheInterface
     */
    private function getGridConfigCache(string $gridName): ConfigCacheInterface
    {
        return new ResourceCheckerConfigCache($this->getGridFile($gridName));
    }

    /**
     * @param string $gridName
     *
     * @return string
     */
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
