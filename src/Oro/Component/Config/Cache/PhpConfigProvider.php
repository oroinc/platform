<?php

namespace Oro\Component\Config\Cache;

use Oro\Component\Config\ResourcesContainer;
use Oro\Component\Config\ResourcesContainerInterface;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Filesystem\Exception\IOException;
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

    /**
     * @var int|bool|null
     * * FALSE if the timestamp is not retrieved yet
     * * NULL if cache file does not exist
     * * an integer for the timestamp of existing cache file
     */
    private $cacheTimestamp = false;

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
     * Indicates whether the configuration cache can be changed during the application lifetime.
     *
     * @return bool
     */
    public function isCacheChangeable(): bool
    {
        return $this->debug;
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
        $cacheTimestamp = $this->getCacheTimestamp();

        return
            null !== $cacheTimestamp
            && $cacheTimestamp <= $timestamp;
    }

    /**
     * Gets timestamp when the configuration cache has been built.
     *
     * @return int|null The last time the cache was built or NULL if the cache is not built yet
     */
    public function getCacheTimestamp(): ?int
    {
        if (false === $this->cacheTimestamp) {
            $cacheTimestamp = null;
            if (\file_exists($this->cacheFile)) {
                $cacheTimestamp = \filemtime($this->cacheFile);
                if (false === $cacheTimestamp) {
                    throw new IOException(\sprintf(
                        'Cannot get modification time for "%s" file.',
                        $this->cacheFile
                    ));
                }
            }
            $this->cacheTimestamp = $cacheTimestamp;
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
                $overrideExistingCacheFile = $this->debug && \file_exists($cache->getPath());

                $resourcesContainer = new ResourcesContainer();
                $config = $this->doLoadConfig($resourcesContainer);
                $this->getCacheAccessor()->save($cache, $config, $resourcesContainer->getResources());
                $this->cacheTimestamp = false;

                if ($overrideExistingCacheFile) {
                    \clearstatcache(false, $cache->getPath());
                }
            }

            $this->config = $this->getCacheAccessor()->load($cache);
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
