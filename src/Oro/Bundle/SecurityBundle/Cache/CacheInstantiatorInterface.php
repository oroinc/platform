<?php

namespace Oro\Bundle\SecurityBundle\Cache;

use Symfony\Component\Cache\Adapter\AdapterInterface;

/**
 * Factory that returns cache instance for given namespace.
 */
interface CacheInstantiatorInterface
{
    public function getCacheInstance(string $namespace): AdapterInterface;
}
