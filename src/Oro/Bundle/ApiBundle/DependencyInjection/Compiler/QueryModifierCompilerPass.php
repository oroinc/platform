<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all query modifiers.
 */
class QueryModifierCompilerPass implements CompilerPassInterface
{
    private const QUERY_MODIFIER_REGISTRY_SERVICE_ID = 'oro_api.entity_serializer.query_modifier_registry';
    private const QUERY_MODIFIER_TAG                 = 'oro.api.query_modifier';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $services = [];
        $queryModifiers = [];
        $taggedServices = $container->findTaggedServiceIds(self::QUERY_MODIFIER_TAG);
        foreach ($taggedServices as $id => $attributes) {
            $services[$id] = new Reference($id);
            foreach ($attributes as $tagAttributes) {
                $queryModifiers[DependencyInjectionUtil::getPriority($tagAttributes)][] = [
                    $id,
                    DependencyInjectionUtil::getRequestType($tagAttributes)
                ];
            }
        }

        if ($queryModifiers) {
            $queryModifiers = DependencyInjectionUtil::sortByPriorityAndFlatten($queryModifiers);
        }

        $container->getDefinition(self::QUERY_MODIFIER_REGISTRY_SERVICE_ID)
            ->setArgument(0, $queryModifiers)
            ->setArgument(1, ServiceLocatorTagPass::register($container, $services));
    }
}
