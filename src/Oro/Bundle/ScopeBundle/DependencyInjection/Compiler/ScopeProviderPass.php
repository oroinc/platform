<?php

namespace Oro\Bundle\ScopeBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ScopeProviderPass implements CompilerPassInterface
{
    const SCOPE_MANAGER = 'oro_scope.scope_manager';
    const SCOPE_PROVIDER_TAG = 'oro_scope.provider';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::SCOPE_MANAGER)) {
            return;
        }
        $taggedServices = $container->findTaggedServiceIds(self::SCOPE_PROVIDER_TAG);
        if (empty($taggedServices)) {
            return;
        }
        $groupedProviders = [];
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $tag) {
                $priority = isset($tag['priority']) ? $tag['priority'] : 0;
                $groupedProviders[$tag['scopeType']][$priority][] = $id;
            }
        }
        $definition = $container->getDefinition(self::SCOPE_MANAGER);
        foreach ($groupedProviders as $scopeType => $providersByPriority) {
            krsort($providersByPriority);
            foreach ($providersByPriority as $providers) {
                foreach ($providers as $provider) {
                    $definition->addMethodCall('addProvider', [$scopeType, new Reference($provider)]);
                }
            }
        }
    }
}
