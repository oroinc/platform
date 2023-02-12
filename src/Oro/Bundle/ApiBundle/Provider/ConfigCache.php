<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Component\Config\Cache\ConfigCacheStateInterface;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * A cache for API configuration.
 */
class ConfigCache implements ConfigCacheStateInterface
{
    private string $configKey;
    private bool $debug;
    private ConfigCacheFactory $configCacheFactory;
    private ?array $data = null;
    private ?ConfigCacheFile $cache = null;
    /**
     * * FALSE if the timestamp is not retrieved yet
     * * NULL if cache file does not exist
     * * an integer for the timestamp of existing cache file
     */
    private int|null|false $cacheTimestamp = false;
    private ?bool $cacheFresh = null;

    public function __construct(
        string $configKey,
        bool $debug,
        ConfigCacheFactory $configCacheFactory
    ) {
        $this->configKey = $configKey;
        $this->debug = $debug;
        $this->configCacheFactory = $configCacheFactory;
    }

    public function getConfig(string $configFile): array
    {
        $configs = $this->getSection(ConfigCacheWarmer::CONFIG);
        if (!isset($configs[$configFile])) {
            throw new \InvalidArgumentException(sprintf('Unknown config "%s".', $configFile));
        }

        return $configs[$configFile];
    }

    public function getAliases(): array
    {
        return $this->getSection(ConfigCacheWarmer::ALIASES);
    }

    /**
     * @return string[]
     */
    public function getExcludedEntities(): array
    {
        return $this->getSection(ConfigCacheWarmer::EXCLUDED_ENTITIES);
    }

    /**
     * @return array [class name => substitute class name, ...]
     */
    public function getSubstitutions(): array
    {
        return $this->getSection(ConfigCacheWarmer::SUBSTITUTIONS);
    }

    public function getExclusions(): array
    {
        return $this->getSection(ConfigCacheWarmer::EXCLUSIONS);
    }

    public function getInclusions(): array
    {
        return $this->getSection(ConfigCacheWarmer::INCLUSIONS);
    }

    /**
     * {@inheritDoc}
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
            $this->cacheFresh = $this->getCache()->isFresh();
        }

        return $this->cacheFresh;
    }

    /**
     * {@inheritDoc}
     */
    public function getCacheTimestamp(): ?int
    {
        if (false === $this->cacheTimestamp) {
            $cacheTimestamp = null;
            $cacheFile = $this->getCache()->getPath();
            if (file_exists($cacheFile)) {
                $cacheTimestamp = filemtime($cacheFile);
                if (false === $cacheTimestamp) {
                    throw new IOException(sprintf('Cannot get modification time for "%s" file.', $cacheFile));
                }
            }
            $this->cacheTimestamp = $cacheTimestamp;
            $this->cacheFresh = null;
        }

        return $this->cacheTimestamp;
    }

    private function getSection(string $section): mixed
    {
        $data = $this->getData();

        return $data[$section];
    }

    private function getData(): array
    {
        if (null === $this->data) {
            $cache = $this->getCache();
            $cacheFile = $cache->getPath();
            if (!$cache->isFresh()) {
                $overrideExistingCacheFile = $this->debug && file_exists($cacheFile);

                $cache->warmUpCache();
                $this->cacheTimestamp = false;
                $this->cacheFresh = null;

                if ($overrideExistingCacheFile) {
                    clearstatcache(false, $cacheFile);
                }
            }

            $data = require $cacheFile;
            if (!\is_array($data)) {
                throw new \LogicException(sprintf('The "%s" must return an array.', $cacheFile));
            }
            $this->data = $data;
        }

        return $this->data;
    }

    private function getCache(): ConfigCacheFile
    {
        if (null === $this->cache) {
            $this->cache = $this->configCacheFactory->getCache($this->configKey);
        }

        return $this->cache;
    }
}
