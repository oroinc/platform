<?php

namespace Oro\Bundle\CacheBundle\Provider;

/**
 * Provides an algorithm to generate short name of cache file.
 *
 * This trait can be used in a cache implementation bases on \Doctrine\Common\Cache\FileCache
 *
 * @property string directory
 */
trait DirectoryAwareFileCacheTrait
{
    /**
     * Sets the cache directory.
     *
     * @param string $directory
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;
    }
}
