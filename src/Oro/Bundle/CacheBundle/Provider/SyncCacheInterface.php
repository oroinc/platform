<?php

namespace Oro\Bundle\CacheBundle\Provider;

/**
 * Provides a way to synchronize a cache between different processes.
 *
 * For example, lets consider the following scenario:
 *  - a cache implementation is based on Doctrine\Common\Cache\FileCache
 *  - you clear and then make some modifications of this cache in one of child process
 *  - when a child process finished you need to get modified values in main process
 * to accomplish this you can implement SyncCacheInterface in your cache class and after a child
 * process is finished call 'sync' method in main process
 */
interface SyncCacheInterface
{
    /**
     * Makes sure the cache is synchronized
     */
    public function sync();
}
