<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class EntityAvatarProviderPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $prioritiesByIds = [];
        $providers = $container->findTaggedServiceIds('oro_entity.avatar_provider');
        foreach ($providers as $id => $tags) {
            foreach ($tags as $attributes) {
                $prioritiesByIds[$id] = isset($attributes['priority']) ? $attributes['priority'] : 0;
            }
        }

        arsort($prioritiesByIds);

        $chainProvider = $container->findDefinition('oro_entity.avatar_provider');
        $chainProvider->addMethodCall(
            'setProviders',
            [
                array_map(
                    function ($id) {
                        return new Reference($id);
                    },
                    array_keys($prioritiesByIds)
                )
            ]
        );
    }
}
