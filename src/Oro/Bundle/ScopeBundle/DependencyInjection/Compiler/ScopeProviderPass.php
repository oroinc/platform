<?php

namespace Oro\Bundle\ScopeBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServiceTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Finds all available scope criteria providers and registers them in the scope manager.
 */
class ScopeProviderPass implements CompilerPassInterface
{
    use TaggedServiceTrait;

    private const MANAGER_SERVICE      = 'oro_scope.scope_manager';
    private const PROVIDER_TAG_NAME    = 'oro_scope.provider';
    private const SCOPE_TYPE_ATTRIBUTE = 'scopeType';

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
                $scopeType = $this->getRequiredAttribute(
                    $attributes,
                    self::SCOPE_TYPE_ATTRIBUTE,
                    $serviceId,
                    self::PROVIDER_TAG_NAME
                );
                $priority = $this->getPriorityAttribute($attributes);
                $groupedProviders[$scopeType][$priority][] = $serviceId;
                if (!isset($services[$serviceId])) {
                    $services[$serviceId] = new Reference($serviceId);
                }
            }
        }
        $providers = [];
        foreach ($groupedProviders as $scopeType => $providersByPriority) {
            $providers[$scopeType] = $this->sortByPriorityAndFlatten($providersByPriority);
        }

        $container->findDefinition(self::MANAGER_SERVICE)
            ->replaceArgument(0, $providers)
            ->replaceArgument(1, ServiceLocatorTagPass::register($container, $services));
    }
}
