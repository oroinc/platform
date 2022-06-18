<?php

namespace Oro\Component\Config\Cache;

use Oro\Component\Config\ResourcesContainer;
use Oro\Component\Config\ResourcesContainerInterface;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Config\Resource\ResourceInterface;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * The base class for configuration that should be stored in a PHP file.
 */
abstract class PhpConfigProvider implements
    ConfigCacheStateInterface,
    WarmableConfigCacheInterface,
    ClearableConfigCacheInterface
{
    /** @var string */
    private $cacheFile;

    /** @var bool */
    private $debug;

    /** @var ConfigCacheInterface|null */
    private $cache;

    /** @var PhpConfigCacheAccessor|null */
    private $cacheAccessor;

    /**
     * @var int|bool|null
     * * FALSE if the timestamp is not retrieved yet
     * * NULL if cache file does not exist
     * * an integer for the timestamp of existing cache file
     */
    private $cacheTimestamp = false;

    /** @var bool|null */
    private $cacheFresh;

    /** @var mixed|null */
    private $config;

    public function __construct(string $cacheFile, bool $debug)
    {
        $this->cacheFile = $cacheFile;
        $this->debug = $debug;
    }

    /**
     * {@inheritdoc}
     */
    public function isCacheFresh(?int $timestamp): bool
    {
        if (null === $timestamp) {
            return true;
        }

        $cacheTimestamp = $this->getCacheTimestamp();
        if (null === $cacheTimestamp || $cacheTimestamp > $timestamp) {
            return false;
        }

        if (null === $this->cacheFresh) {
            $this->cacheFresh = $this->getConfigCache()->isFresh();
        }

        return $this->cacheFresh;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheTimestamp(): ?int
    {
        if (false === $this->cacheTimestamp) {
            $cacheTimestamp = null;
            if (file_exists($this->cacheFile)) {
                $cacheTimestamp = filemtime($this->cacheFile);
                if (false === $cacheTimestamp) {
                    throw new IOException(sprintf('Cannot get modification time for "%s" file.', $this->cacheFile));
                }
            }
            $this->cacheTimestamp = $cacheTimestamp;
            $this->cacheFresh = null;
        }

        return $this->cacheTimestamp;
    }

    /**
     * {@inheritdoc}
     */
    public function clearCache(): void
    {
        $this->config = null;
        $this->cacheTimestamp = false;
        $this->cacheFresh = null;
        $this->getCacheAccessor()->remove($this->getConfigCache());
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
                $overrideExistingCacheFile = $this->debug && file_exists($cache->getPath());

                $resourcesContainer = new ResourcesContainer();
                $config = $this->doLoadConfig($resourcesContainer);
                $this->getCacheAccessor()->save($cache, $config, $resourcesContainer->getResources());
                $this->cacheTimestamp = false;
                $this->cacheFresh = null;

                if ($overrideExistingCacheFile) {
                    clearstatcache(false, $cache->getPath());
                }
            }

            $this->config = $this->getCacheAccessor()->load($cache);
        }
    }

    /**
     * Gets a resource that represents the cache file
     * and can be used by depended configuration caches to track if this cache is changed.
     */
    public function getCacheResource(): ResourceInterface
    {
        return new FileResource($this->cacheFile);
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

    private function getConfigCache(): ConfigCacheInterface
    {
        if (null === $this->cache) {
            $this->cache = new ConfigCache($this->cacheFile, $this->debug);
        }

        return $this->cache;
    }

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
