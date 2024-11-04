<?php

namespace Oro\Bundle\SecurityBundle\Cache;

use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;

/**
 * Cache instantiator that returns Filesystem cache instance.
 */
class FilesystemCacheInstantiator implements CacheInstantiatorInterface
{
    private string $lifetime;
    private string $cacheDirectory;

    private array $caches = [];

    public function __construct(string $lifetime, string $cacheDirectory)
    {
        $this->lifetime = $lifetime;
        $this->cacheDirectory = $cacheDirectory;
    }

    #[\Override]
    public function getCacheInstance(string $namespace): AdapterInterface
    {
        if (!\array_key_exists($namespace, $this->caches)) {
            $this->caches[$namespace] = new PhpFilesAdapter(
                $namespace,
                $this->lifetime,
                $this->cacheDirectory
            );
        }

        return $this->caches[$namespace];
    }
}
