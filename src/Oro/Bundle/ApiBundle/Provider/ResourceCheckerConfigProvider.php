<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Component\Config\Cache\ConfigCache;
use Oro\Component\Config\Cache\PhpConfigCacheAccessor;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * Provides API action depended configuration for API resources
 * loaded from "api_resources" section of "Resources/config/oro/features.yml" configuration file.
 */
class ResourceCheckerConfigProvider
{
    private string $cacheFile;
    private ?array $config = null;
    private ?ConfigCacheInterface $cache = null;
    private ?PhpConfigCacheAccessor $cacheAccessor = null;

    public function __construct(string $cacheFile)
    {
        $this->cacheFile = $cacheFile;
    }

    public function getApiResourceFeatures(string $entityClass, string $action): array
    {
        $config = $this->getConfig();

        return $config[$entityClass][$action] ?? [];
    }

    public function getApiResources(string $feature): array
    {
        $result = [];
        $config = $this->getConfig();
        foreach ($config as $entityClass => $entityConfig) {
            $actions = [];
            foreach ($entityConfig as $action => $features) {
                if (\in_array($feature, $features, true)) {
                    $actions[] = $action;
                }
            }
            if ($actions) {
                $result[] = [$entityClass, $actions];
            }
        }

        return $result;
    }

    public function addApiResource(string $feature, string $entityClass, array $actions): void
    {
        foreach ($actions as $action) {
            $this->config[$entityClass][$action][] = $feature;
        }
    }

    public function startBuild(): void
    {
        $this->config = [];
    }

    public function flush(): void
    {
        $this->getCacheAccessor()->save($this->getConfigCache(), $this->config);
    }

    public function clear(): void
    {
        $this->config = null;
        $this->getCacheAccessor()->remove($this->getConfigCache());
    }

    private function getConfig(): array
    {
        if (null === $this->config) {
            if (!file_exists($this->cacheFile)) {
                throw new FileNotFoundException(null, 0, null, $this->cacheFile);
            }
            $this->config = $this->getCacheAccessor()->load($this->getConfigCache());
        }

        return $this->config;
    }

    private function getConfigCache(): ConfigCacheInterface
    {
        if (null === $this->cache) {
            $this->cache = new ConfigCache($this->cacheFile, false);
        }

        return $this->cache;
    }

    private function getCacheAccessor(): PhpConfigCacheAccessor
    {
        if (null === $this->cacheAccessor) {
            $this->cacheAccessor = new PhpConfigCacheAccessor(function ($config) {
                if (!\is_array($config)) {
                    throw new \LogicException('Expected an array.');
                }
            });
        }

        return $this->cacheAccessor;
    }
}
