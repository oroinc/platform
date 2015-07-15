<?php

namespace Oro\Bundle\CacheBundle\Provider;

/**
 * Allows to change a file cache directory.
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
