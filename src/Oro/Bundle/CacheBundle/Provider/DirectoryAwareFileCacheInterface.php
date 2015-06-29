<?php

namespace Oro\Bundle\CacheBundle\Provider;

interface DirectoryAwareFileCacheInterface
{
    /**
     * Gets the cache directory.
     *
     * @return string
     */
    public function getDirectory();

    /**
     * Sets the cache directory.
     *
     * @param string $directory
     */
    public function setDirectory($directory);
}
