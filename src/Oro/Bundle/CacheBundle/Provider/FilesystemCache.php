<?php

namespace Oro\Bundle\CacheBundle\Provider;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Marshaller\MarshallerInterface;

/**
 * This class extends Symfony FilesystemAdapter in order to be able changing and retrieve a file cache directory
 */
class FilesystemCache extends FilesystemAdapter implements DirectoryAwareFileCacheInterface
{
    use NamespaceVersionSyncTrait;
    use DirectoryAwareFileCacheTrait;
    use ShortFileNameGeneratorTrait;

    public function __construct(
        string $namespace = '',
        int $defaultLifetime = 0,
        string $directory = null,
        MarshallerInterface $marshaller = null
    ) {
        if ($directory) {
            $this->setDirectory($directory . DIRECTORY_SEPARATOR . $namespace);
        }
        parent::__construct($namespace, $defaultLifetime, $directory, $marshaller);
    }
}
