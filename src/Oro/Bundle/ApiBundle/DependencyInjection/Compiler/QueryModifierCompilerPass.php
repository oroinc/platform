<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;

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
        // find query modifiers
        $queryModifiers = [];
        $taggedServices = $container->findTaggedServiceIds(self::QUERY_MODIFIER_TAG);
        foreach ($taggedServices as $id => $attributes) {
            if (!$container->getDefinition($id)->isPublic()) {
                throw new LogicException(
                    \sprintf('The query modifier service "%s" should be public.', $id)
                );
            }
            foreach ($attributes as $tagAttributes) {
                $queryModifiers[DependencyInjectionUtil::getPriority($tagAttributes)][] = [
                    $id,
                    DependencyInjectionUtil::getAttribute($tagAttributes, 'requestType', null)
                ];
            }
        }
        if (empty($queryModifiers)) {
            return;
        }

        // sort by priority and flatten
        $queryModifiers = DependencyInjectionUtil::sortByPriorityAndFlatten($queryModifiers);

        // register
        $container->getDefinition(self::QUERY_MODIFIER_REGISTRY_SERVICE_ID)
            ->replaceArgument(0, $queryModifiers);
    }
}
