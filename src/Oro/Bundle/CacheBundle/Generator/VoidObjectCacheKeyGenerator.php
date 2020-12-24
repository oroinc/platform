<?php

namespace Oro\Bundle\CacheBundle\Generator;

/**
 * Generates random cache key just for backward compatibility without hitting performance.
 */
class VoidObjectCacheKeyGenerator extends ObjectCacheKeyGenerator
{
    public function __construct()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function generate($object, string $scope)
    {
        return sha1(uniqid(get_class($object), true));
    }
}
