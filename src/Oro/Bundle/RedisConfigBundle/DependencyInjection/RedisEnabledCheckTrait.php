<?php

namespace Oro\Bundle\RedisConfigBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This trait contains methods to validate and check that redis is enabled and configured as a cache
 * It works with ContainerBuilder and can be used in compiler passes
 */
trait RedisEnabledCheckTrait
{
    private function isParameterDefined(ContainerBuilder $container, string $paramName): bool
    {
        return $container->hasParameter($paramName) && null !== $container->getParameter($paramName);
    }

    protected function isRedisEnabledForCache(ContainerBuilder $container): bool
    {
        return $this->isParameterDefined($container, 'redis_dsn_cache');
    }

    protected function isRedisEnabledForDoctrine(ContainerBuilder $container): bool
    {
        return $this->isParameterDefined($container, 'redis_dsn_doctrine');
    }

    protected function isRedisEnabledForLayout(ContainerBuilder $container): bool
    {
        return $this->isParameterDefined($container, 'redis_dsn_layout');
    }
}
