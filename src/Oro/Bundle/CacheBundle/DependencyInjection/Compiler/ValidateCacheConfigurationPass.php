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
                if (!$this->getNamespace($container->getDefinition((string)$cacheProviderRef))) {
                    throw new \InvalidArgumentException(sprintf(
                        'The namespace for the "%s" cache service must be defined.'
                        . ' Make sure that the "setNamespace" method call exists in the service definition.',
                        (string)$cacheProviderRef
                    ));
                }
            }
        }
    }

    private function getNamespace(Definition $cacheProviderDef): ?string
    {
        $namespace = null;
        $methodCalls = $cacheProviderDef->getMethodCalls();
        foreach ($methodCalls as [$method, $arguments]) {
            if ('setNamespace' === $method) {
                $namespace = reset($arguments);
            }
        }

        return $namespace;
    }
}
