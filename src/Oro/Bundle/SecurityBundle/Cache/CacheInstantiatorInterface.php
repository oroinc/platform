<?php

namespace Oro\Bundle\SecurityBundle\Cache;

use Doctrine\Common\Cache\Cache;

/**
 * Factory that returns cache instance for given namespace.
 */
interface CacheInstantiatorInterface
{
    public function getCacheInstance(string $namespace): Cache;
}
