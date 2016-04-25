<?php

namespace Oro\Bundle\UIBundle\Asset;

use Doctrine\Common\Cache\CacheProvider;

/**
 * Provides routines to work with a version of asset packages that can be changed at runtime.
 */
class DynamicAssetVersionManager
{
    /** @var array */
    protected $localCache;

    /** @var CacheProvider */
    protected $cache;

    /**
     * @param CacheProvider $cache
     */
    public function __construct(CacheProvider $cache)
    {
        $this->cache      = $cache;
        $this->localCache = [];
    }

    /**
     * Gets the current version of a given asset package.
     *
     * @param string $packageName
     *
     * @return string the current version of the given asset package
     */
    public function getAssetVersion($packageName)
    {
        $versionNumber = $this->getCachedAssetVersion($packageName);

        return 0 !== $versionNumber
            ? (string)$versionNumber
            : '';
    }

    /**
     * Increase the number of the current version of a given asset package.
     *
     * @param string $packageName
     */
    public function updateAssetVersion($packageName)
    {
        $versionNumber = $this->getCachedAssetVersion($packageName) + 1;

        $this->localCache[$packageName] = $versionNumber;
        $this->cache->save($packageName, $versionNumber);
    }

    /**
     * @param string $packageName
     *
     * @return int
     */
    protected function getCachedAssetVersion($packageName)
    {
        if (!array_key_exists($packageName, $this->localCache)) {
            $version = $this->cache->fetch($packageName);

            $this->localCache[$packageName] = false !== $version
                ? $version
                : 0;
        }

        return $this->localCache[$packageName];
    }
}
