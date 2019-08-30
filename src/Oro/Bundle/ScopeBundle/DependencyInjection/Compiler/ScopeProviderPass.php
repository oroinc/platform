<?php

namespace Oro\Bundle\ScopeBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Finds all available scope criteria providers and registers them in the scope manager.
 */
class ScopeProviderPass implements CompilerPassInterface
{
    private const MANAGER_SERVICE      = 'oro_scope.scope_manager';
    private const PROVIDER_TAG_NAME    = 'oro_scope.provider';
    private const SCOPE_TYPE_ATTRIBUTE = 'scopeType';
    private const PRIORITY_ATTRIBUTE   = 'priority';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $services = [];
        $groupedProviders = [];
        $taggedServices = $container->findTaggedServiceIds(self::PROVIDER_TAG_NAME);
        foreach ($taggedServices as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                if (empty($attributes[self::SCOPE_TYPE_ATTRIBUTE])) {
                    throw new \InvalidArgumentException(sprintf(
                        'The tag attribute "%s" is required for service "%s".',
                        self::SCOPE_TYPE_ATTRIBUTE,
                        $serviceId
                    ));
                }
                $scopeType = $attributes[self::SCOPE_TYPE_ATTRIBUTE];
                $priority = $attributes[self::PRIORITY_ATTRIBUTE] ?? 0;
                $groupedProviders[$scopeType][$priority][] = $serviceId;
                if (!isset($services[$serviceId])) {
                    $services[$serviceId] = new Reference($serviceId);
                }
            }
        }
        $providers = [];
        foreach ($groupedProviders as $scopeType => $providersByPriority) {
            krsort($providersByPriority);
            $providers[$scopeType] = array_merge(...$providersByPriority);
        }

        $container->findDefinition(self::MANAGER_SERVICE)
            ->setArgument(0, ServiceLocatorTagPass::register($container, $services))
            ->setArgument(1, $providers);
    }
}
