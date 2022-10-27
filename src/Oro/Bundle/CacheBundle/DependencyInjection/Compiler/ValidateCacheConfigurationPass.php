<?php

namespace Oro\Bundle\CacheBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Validates that all caches have namespaces.
 */
class ValidateCacheConfigurationPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $methodCalls = $container->getDefinition(CacheConfigurationPass::MANAGER_SERVICE_KEY)->getMethodCalls();
        foreach ($methodCalls as [$method, $arguments]) {
            if ('registerCacheProvider' === $method) {
                $cacheProviderRef = reset($arguments);
                if (!$this->hasNamespace($container->getDefinition((string)$cacheProviderRef))) {
                    throw new \InvalidArgumentException(sprintf(
                        'The namespace for the "%s" cache service must be defined.'
                        . ' Make sure that the "setNamespace" method call exists in the service definition.',
                        (string)$cacheProviderRef
                    ));
                }
            }
        }
    }

    private function hasNamespace(Definition $cacheProviderDef): bool
    {
        $poolTag = $cacheProviderDef->getTag('cache.pool');
        if (!empty($poolTag)) {
            foreach ($poolTag as $value) {
                if (array_key_exists('namespace', $value)) {
                    return true;
                }
            }
        }
        return false;
    }
}
