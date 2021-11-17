<?php

namespace Oro\Component\Testing\Unit\Cache;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\ChainAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;

/**
 * A base class for creating a test caches.
 */
trait CacheTrait
{
    public function getArrayCache(
        int $defaultLifetime = 0,
        bool $storeSerialized = false,
        float $maxLifetime = 0,
        int $maxItems = 0
    ): Cache|CacheProvider {
        return $this->getCache(new ArrayAdapter($defaultLifetime, $storeSerialized, $maxLifetime, $maxItems));
    }

    public function getChainCache(array $adapters = [], float $maxLifetime = 0): Cache|CacheProvider
    {
        if (empty($adapters)) {
            $adapters = [new ArrayAdapter(0, false)];
        }

        return $this->getCache(new ChainAdapter($adapters, $maxLifetime));
    }

    public function getNullCache(): Cache|CacheProvider
    {
        return $this->getCache(new NullAdapter());
    }

    public function getCache(AdapterInterface $adapter): Cache|CacheProvider
    {
        return DoctrineProvider::wrap($adapter);
    }
}
