<?php

namespace Oro\Bundle\RedisConfigBundle\Factory;

use Oro\Bundle\SecurityBundle\Cache\CacheInstantiatorInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;

/**
 * Cache instantiator that returns Redis cache instance.
 */
class RedisCacheInstantiator implements CacheInstantiatorInterface
{
    private $adapter;
    private string $lifetime;

    private array $caches = [];

    public function __construct($adapter, string $lifetime)
    {
        $this->lifetime = $lifetime;
        $this->adapter = $adapter;
    }

    #[\Override]
    public function getCacheInstance(string $namespace): AdapterInterface
    {
        if (!\array_key_exists($namespace, $this->caches)) {
            $this->caches[$namespace] = new RedisAdapter(
                $this->adapter,
                $namespace,
                $this->lifetime
            );
        }

        return $this->caches[$namespace];
    }
}
