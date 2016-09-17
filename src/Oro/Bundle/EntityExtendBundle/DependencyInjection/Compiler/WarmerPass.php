<?php

namespace Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class WarmerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('oro_entity_extend.cache_warmer')) {
            return;
        }

        $taggedServices = $container->findTaggedServiceIds('oro_entity_extend.warmer');
        if (!$taggedServices) {
            return;
        }

        $definition = $container->getDefinition('oro_entity_extend.cache_warmer');

        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('addWarmer', [new Reference($id)]);
        }
    }
}
