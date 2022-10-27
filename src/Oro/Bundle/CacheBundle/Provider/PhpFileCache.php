<?php

namespace Oro\Bundle\CacheBundle\Provider;

use Symfony\Component\Cache\Adapter\PhpFilesAdapter as BasePhpFileCache;

/**
 * This class extends Symfony PhpFilesAdapter in order to be able changing and retrieve a file cache directory
 */
class PhpFileCache extends BasePhpFileCache implements DirectoryAwareFileCacheInterface
{
    use DirectoryAwareFileCacheTrait;

    public function __construct(
        string $namespace = '',
        int $defaultLifetime = 0,
        string $directory = null,
        bool $appendOnly = false
    ) {
        if ($directory) {
            $this->setDirectory($directory . DIRECTORY_SEPARATOR . $namespace);
        }
        parent::__construct($namespace, $defaultLifetime, $directory, $appendOnly);
    }
}
