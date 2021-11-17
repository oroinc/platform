<?php

namespace Oro\Bundle\CacheBundle\Provider;

/**
 * Allows changing and retrieve a file cache directory.
 *
 * This trait can be used in a cache implementation based on Symfony\Component\Cache\Adapter\FilesystemAdapter
 *
 * @property string $directory
 */
trait DirectoryAwareFileCacheTrait
{
    public function setDirectory(string $directory): void
    {
        $this->directory = $directory;
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }
}
