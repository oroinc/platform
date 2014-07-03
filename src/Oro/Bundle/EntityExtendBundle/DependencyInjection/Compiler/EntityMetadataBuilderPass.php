<?php

namespace Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class EntityMetadataBuilderPass implements CompilerPassInterface
{
    const SERVICE_ID = 'oro_entity_extend.entity_metadata_builder';
    const TAG_NAME   = 'oro_entity_extend.entity_metadata_builder';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::SERVICE_ID)) {
            return;
        }

        // find builders
        $builders       = [];
        $taggedServices = $container->findTaggedServiceIds(self::TAG_NAME);
        foreach ($taggedServices as $id => $attributes) {
            $priority              = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
            $builders[$priority][] = new Reference($id);
        }
        if (empty($builders)) {
            return;
        }

        // sort by priority and flatten
        krsort($builders);
        $builders = call_user_func_array('array_merge', $builders);

        // register
        $serviceDef = $container->getDefinition(self::SERVICE_ID);
        foreach ($builders as $builder) {
            $serviceDef->addMethodCall('addBuilder', [$builder]);
        }
    }
}
