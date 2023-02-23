<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all resolvers for predefined entity identifiers.
 */
class EntityIdResolverCompilerPass implements CompilerPassInterface
{
    use ApiTaggedServiceTrait;

    private const RESOLVER_REGISTRY_SERVICE_ID = 'oro_api.entity_id_resolver_registry';
    private const RESOLVER_TAG = 'oro.api.entity_id_resolver';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $services = [];
        $resolvers = [];
        $taggedServices = $container->findTaggedServiceIds(self::RESOLVER_TAG);
        foreach ($taggedServices as $id => $tags) {
            $services[$id] = new Reference($id);
            foreach ($tags as $attributes) {
                $resolvers[$this->getPriorityAttribute($attributes)][] = [
                    $id,
                    $this->getRequestTypeAttribute($attributes),
                    $this->getRequiredAttribute($attributes, 'id', $id, self::RESOLVER_TAG),
                    $this->getRequiredAttribute($attributes, 'class', $id, self::RESOLVER_TAG)
                ];
            }
        }

        if ($resolvers) {
            // sort by priority and convert to the following array:
            // [entity id => [entity class => [resolver service id, request type expression], ...], ...]
            $resolvers = $this->sortByPriorityAndFlatten($resolvers);
            $restructured = [];
            foreach ($resolvers as [$serviceId, $requestTypeExpr, $entityId, $entityClass]) {
                $restructured[$entityId][$entityClass][] = [$serviceId, $requestTypeExpr];
            }
            $resolvers = $restructured;
        }

        $container->getDefinition(self::RESOLVER_REGISTRY_SERVICE_ID)
            ->setArgument('$resolvers', $resolvers)
            ->setArgument('$container', ServiceLocatorTagPass::register($container, $services));
    }
}
