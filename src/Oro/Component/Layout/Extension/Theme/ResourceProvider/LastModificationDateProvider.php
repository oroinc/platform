<?php

namespace Oro\Component\Layout\Extension\Theme\ResourceProvider;

use Oro\Component\Config\Cache\ConfigCache;
use Oro\Component\Config\Cache\PhpConfigCacheAccessor;
use Symfony\Component\Config\ConfigCacheInterface;

/**
 * Allows to get and update the cache for the last modification date of theme resources.
 */
class LastModificationDateProvider
{
    /** @var string */
    private $cacheFile;

    /** @var bool */
    private $debug;

    /** @var ConfigCacheInterface */
    private $cache;

    /** @var PhpConfigCacheAccessor */
    private $cacheAccessor;

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
     * @return \DateTime|null
     */
    public function getLastModificationDate(): ?\DateTime
    {
        $value = $this->getCacheAccessor()->load($this->getCache());

        return $value instanceof \DateTime ? $value : null;
    }

    /**
     * @param \DateTime|null $date
     */
    public function updateLastModificationDate(?\DateTime $date): void
    {
        $this->getCacheAccessor()->save($this->getCache(), $date ?? false);
    }

    /**
     * @return ConfigCacheInterface
     */
    private function getCache(): ConfigCacheInterface
    {
        if (null === $this->cache) {
            $this->cache = new ConfigCache($this->cacheFile, $this->debug);
            if (!$this->cache->isFresh()) {
                $this->getCacheAccessor()->save($this->cache, false);
            }
        }

        return $this->cache;
    }

    /**
     * @return PhpConfigCacheAccessor
     */
    private function getCacheAccessor(): PhpConfigCacheAccessor
    {
        if (null === $this->cacheAccessor) {
            $this->cacheAccessor = new PhpConfigCacheAccessor(function ($value) {
                if (!$value instanceof \DateTime && false !== $value) {
                    throw new \LogicException('Expected an instance of DateTime or boolean FALSE.');
                }
            });
        }

        return $this->cacheAccessor;
    }
}
