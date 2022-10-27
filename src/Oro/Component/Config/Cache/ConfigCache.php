<?php

namespace Oro\Component\Config\Cache;

use Oro\Component\Config\Resource\SelfCheckingResourceChecker;
use Symfony\Component\Config\ResourceCheckerConfigCache;

/**
 * This class uses a file on disk to cache configuration data.
 * Unlike Symfony's ConfigCache, this implementation does not write metadata when debugging is disabled
 * and provides a way to check the cache freshness based on freshness of other caches.
 */
class ConfigCache extends ResourceCheckerConfigCache
{
    private bool $debug;

    /** @var ConfigCacheStateInterface[] */
    private ?array $dependencies = [];

    /**
     * @param string $file  The absolute cache path
     * @param bool   $debug Whether debugging is enabled or not
     */
    public function __construct(string $file, bool $debug)
    {
        $this->debug = $debug;

        $checkers = [];
        if ($this->debug) {
            $checkers = [new SelfCheckingResourceChecker($debug)];
        }

        parent::__construct($file, $checkers);
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh(): bool
    {
        if (!$this->debug && is_file($this->getPath())) {
            return true;
        }

        $result = parent::isFresh();
        if ($result && $this->dependencies && $this->debug) {
            $cacheTimestamp = filemtime($this->getPath());
            if (false !== $cacheTimestamp) {
                foreach ($this->dependencies as $dependency) {
                    if (!$dependency->isCacheFresh($cacheTimestamp)) {
                        $result = false;
                        break;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function write($content, array $metadata = null): void
    {
        if (null !== $metadata && !$this->debug) {
            $metadata = null;
        }

        parent::write($content, $metadata);
    }

    /**
     * Registers a cache this cache depends on.
     */
    public function addDependency(ConfigCacheStateInterface $configCache): void
    {
        $this->dependencies[] = $configCache;
    }

    /**
     * Makes sure that all caches this cache depends on were warmed up.
     */
    protected function ensureDependenciesWarmedUp(): void
    {
        if ($this->dependencies && $this->debug) {
            $cacheTimestamp = is_file($this->getPath())
                ? filemtime($this->getPath())
                : false;
            if (false === $cacheTimestamp) {
                $cacheTimestamp = PHP_INT_MAX;
            }
            foreach ($this->dependencies as $dependency) {
                if ($dependency instanceof WarmableConfigCacheInterface
                    && !$dependency->isCacheFresh($cacheTimestamp)) {
                    $dependency->warmUpCache();
                }
            }
        }
    }
}
