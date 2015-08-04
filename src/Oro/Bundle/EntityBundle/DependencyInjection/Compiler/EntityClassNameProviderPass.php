<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class EntityClassNameProviderPass implements CompilerPassInterface
{
    const CHAIN_SERVICE = 'oro_entity.entity_class_name_provider';
    const PROVIDER_TAG_NAME = 'oro_entity.class_name_provider';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::CHAIN_SERVICE)) {
            return;
        }

        // find providers
        $providers      = [];
        $taggedServices = $container->findTaggedServiceIds(self::PROVIDER_TAG_NAME);
        foreach ($taggedServices as $id => $attributes) {
            $priority               = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
            $providers[$priority][] = new Reference($id);
        }
        if (empty($providers)) {
            return;
        }

        // sort by priority and flatten
        krsort($providers);
        $providers = call_user_func_array('array_merge', $providers);

        // register
        $resolverDef = $container->getDefinition(self::CHAIN_SERVICE);
        foreach ($providers as $provider) {
            $resolverDef->addMethodCall('addProvider', [$provider]);
        }
    }
}
