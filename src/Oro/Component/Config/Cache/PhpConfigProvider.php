<?php

namespace Oro\Component\Config\Cache;

use Oro\Component\Config\ResourcesContainer;
use Oro\Component\Config\ResourcesContainerInterface;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * The base class for configuration that should be stored in a PHP file.
 */
abstract class PhpConfigProvider implements WarmableConfigCacheInterface, ClearableConfigCacheInterface
{
    /** @var string */
    private $cacheFile;

    /** @var bool */
    private $debug;

    /** @var ConfigCacheInterface|null */
    private $cache;

    /** @var PhpConfigCacheAccessor|null */
    private $cacheAccessor;

    /** @var int|null */
    private $cacheTimestamp;

    /** @var mixed|null */
    private $config;

    /**
     * @param string $cacheFile
     * @param bool   $debug
     */
    public function __construct(string $cacheFile, bool $debug)
    {
        $this->cacheFile = $cacheFile;
        $this->debug = $debug;
    }

    /**
     * Checks if the configuration cache has not been changed since the given timestamp.
     *
     * @param int $timestamp The time to compare with the last time the cache was built
     *
     * @return bool TRUE if the the cache has not been changed; otherwise, FALSE
     */
    public function isCacheFresh(int $timestamp): bool
    {
        if (null === $this->cacheTimestamp && \file_exists($this->cacheFile)) {
            $this->cacheTimestamp = \filemtime($this->cacheFile);
        }

        return
            null !== $this->cacheTimestamp
            && $this->cacheTimestamp <= $timestamp;
    }

    /**
     * Gets timestamp when the configuration cache has been built.
     *
     * @return int The last time the cache was built
     */
    public function getCacheTimestamp(): int
    {
        if (null === $this->cacheTimestamp) {
            if (\file_exists($this->cacheFile)) {
                $this->cacheTimestamp = \filemtime($this->cacheFile);
            } else {
                $this->ensureCacheWarmedUp();
            }
        }

        return $this->cacheTimestamp;
    }

    /**
     * {@inheritdoc}
     */
    public function clearCache(): void
    {
        $this->config = null;
        $this->cacheTimestamp = null;
        if (\is_file($this->cacheFile)) {
            $fs = new Filesystem();
            $fs->remove($this->cacheFile);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function warmUpCache(): void
    {
        $this->clearCache();
        $this->ensureCacheWarmedUp();
    }

    /**
     * Makes sure that configuration cache was warmed up.
     */
    public function ensureCacheWarmedUp(): void
    {
        if (null === $this->config) {
            $cache = $this->getConfigCache();
            if (!$cache->isFresh()) {
                $resourcesContainer = new ResourcesContainer();
                $config = $this->doLoadConfig($resourcesContainer);
                $this->getCacheAccessor()->save($cache, $config, $resourcesContainer->getResources());
            }

            $this->config = $this->getCacheAccessor()->load($cache);
            $this->cacheTimestamp = filemtime($cache->getPath());
        }
    }

    /**
     * @return mixed
     */
    protected function doGetConfig()
    {
        $this->ensureCacheWarmedUp();

        return $this->config;
    }

    /**
     * @param ResourcesContainerInterface $resourcesContainer
     *
     * @return mixed
     */
    abstract protected function doLoadConfig(ResourcesContainerInterface $resourcesContainer);

    /**
     * @param mixed $config
     *
     * @throws \LogicException if the given config is not valid
     */
    abstract protected function assertLoaderConfig($config): void;

    /**
     * @return ConfigCacheInterface
     */
    private function getConfigCache(): ConfigCacheInterface
    {
        if (null === $this->cache) {
            $this->cache = new ConfigCache($this->cacheFile, $this->debug);
        }

        return $this->cache;
    }

    /**
     * @return PhpConfigCacheAccessor
     */
    private function getCacheAccessor(): PhpConfigCacheAccessor
    {
        if (null === $this->cacheAccessor) {
            $this->cacheAccessor = new PhpConfigCacheAccessor(function ($config) {
                $this->assertLoaderConfig($config);
            });
        }

        return $this->cacheAccessor;
    }
}
