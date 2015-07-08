<?php

namespace Oro\Bundle\CacheBundle\Provider;

/**
 * Provides an implementation for 'sync' method of SyncCacheInterface for caches which
 * can be synchronized by resetting a namespace version.
 *
 * This trait can be used in a cache implementation bases on \Doctrine\Common\Cache\CacheProvider
 *
 * @method string getNamespace
 * @method string setNamespace($namespace)
 */
trait NamespaceVersionSyncTrait
{
    /**
     * Makes sure the cache is synchronized
     */
    public function sync()
    {
        // set $this->namespaceVersion to NULL; it will force to load latest cache version from the file system
        $this->setNamespace($this->getNamespace());
    }
}
