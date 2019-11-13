<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all resolvers for predefined entity identifiers.
 */
class EntityIdResolverCompilerPass implements CompilerPassInterface
{
    private const RESOLVER_REGISTRY_SERVICE_ID = 'oro_api.entity_id_resolver_registry';
    private const RESOLVER_TAG                 = 'oro.api.entity_id_resolver';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $resolvers = [];
        $taggedServices = $container->findTaggedServiceIds(self::RESOLVER_TAG);
        foreach ($taggedServices as $id => $attributes) {
            $container->getDefinition($id)->setPublic(true);
            foreach ($attributes as $tagAttributes) {
                $entityId = DependencyInjectionUtil::getRequiredAttribute(
                    $tagAttributes,
                    'id',
                    $id,
                    self::RESOLVER_TAG
                );
                $entityClass = DependencyInjectionUtil::getRequiredAttribute(
                    $tagAttributes,
                    'class',
                    $id,
                    self::RESOLVER_TAG
                );
                $resolvers[DependencyInjectionUtil::getPriority($tagAttributes)][] = [
                    $id,
                    DependencyInjectionUtil::getRequestType($tagAttributes),
                    $entityId,
                    $entityClass
                ];
            }
        }

        $services = [];
        if ($resolvers) {
            // sort by priority and convert to the following array:
            // [entity id => [entity class => [resolver service id, request type expression], ...], ...]
            $resolvers = DependencyInjectionUtil::sortByPriorityAndFlatten($resolvers);
            $restructured = [];
            foreach ($resolvers as list($serviceId, $requestTypeExpr, $entityId, $entityClass)) {
                $services[$serviceId] = new Reference($serviceId);
                $restructured[$entityId][$entityClass][] = [$serviceId, $requestTypeExpr];
            }
            $resolvers = $restructured;
        }

        $container->getDefinition(self::RESOLVER_REGISTRY_SERVICE_ID)
            ->setArgument(0, $resolvers)
            ->setArgument(1, ServiceLocatorTagPass::register($container, $services));
    }
}
