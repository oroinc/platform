<?php

namespace Oro\Bundle\CacheBundle\Provider;

/**
 * Interface for directory path awareness
 */
interface DirectoryAwareFileCacheInterface
{
    /**
     * Gets the cache directory.
     */
    public function getDirectory(): string;

    /**
     * Sets the cache directory.
     */
    public function setDirectory(string $directory): void;
}
