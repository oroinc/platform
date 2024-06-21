<?php

namespace Oro\Bundle\SecurityBundle\Cache;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\CacheBundle\Provider\FilesystemCache;

/**
 * Returns cache object for given namespace.
 */
class CacheInstantiator implements CacheInstantiatorInterface
{
    protected $cacheDir;
    protected $client = null;

    private array $caches = [];

    public function __construct($cacheDir, $client = null)
    {
        $this->client = $client;
        $this->cacheDir = $cacheDir;
    }

    public function getCacheInstance(string $namespace): Cache
    {
        if (!\array_key_exists($namespace, $this->caches)) {
            $this->caches[$namespace] = $this->getCache($namespace);
        }

        return $this->caches[$namespace];
    }

    protected function getCache(string $namespace): Cache
    {
        if ($this->client) {
            $cache =  new DoctrineAclQueriesPredisCache($this->client);
        } else {
            $cache =  new FilesystemCache($this->cacheDir);
        }

        $cache->setNamespace($namespace);

        return $cache;
    }
}
