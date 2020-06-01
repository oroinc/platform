<?php

namespace Oro\Component\Config\Cache;

use Symfony\Component\Config\Resource\SelfCheckingResourceChecker;
use Symfony\Component\Config\ResourceCheckerConfigCache;

/**
 * This class uses a file on disk to cache configuration data.
 * Unlike Symfony's ConfigCache, this implementation does not write metadata when debugging is disabled
 * and provides a way to check the cache freshness based on freshness of other caches.
 */
class ConfigCache extends ResourceCheckerConfigCache
{
    /** @var bool */
    private $debug;

    /** @var ConfigCacheStateInterface[]|null */
    private $dependencies;

    /**
     * @param string $file  The absolute cache path
     * @param bool   $debug Whether debugging is enabled or not
     */
    public function __construct(string $file, bool $debug)
    {
        $this->debug = $debug;

        $checkers = [];
        if ($this->debug) {
            $checkers = [new SelfCheckingResourceChecker()];
        }

        parent::__construct($file, $checkers);
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh()
    {
        if (!$this->debug && \is_file($this->getPath())) {
            return true;
        }

        if (!$this->debug || !$this->dependencies) {
            return parent::isFresh();
        }

        $result = parent::isFresh();
        if ($result) {
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
    public function write($content, array $metadata = null)
    {
        if (!$this->debug && null !== $metadata) {
            $metadata = null;
        }

        parent::write($content, $metadata);
    }

    /**
     * Registers a cache this cache depends on.
     *
     * @param ConfigCacheStateInterface $configCache
     */
    public function addDependency(ConfigCacheStateInterface $configCache): void
    {
        $this->dependencies[] = $configCache;
    }
}
