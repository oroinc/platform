<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class EntityAliasProviderPass implements CompilerPassInterface
{
    const LOADER_SERVICE          = 'oro_entity.entity_alias_loader';
    const ALIAS_PROVIDER_TAG_NAME = 'oro_entity.alias_provider';
    const CLASS_PROVIDER_TAG_NAME = 'oro_entity.class_provider';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::LOADER_SERVICE)) {
            return;
        }

        // find providers
        $classProviders = [];
        $aliasProviders = [];
        $taggedServices = $container->findTaggedServiceIds(self::CLASS_PROVIDER_TAG_NAME);
        foreach ($taggedServices as $id => $attributes) {
            $classProviders[] = new Reference($id);
        }
        $taggedServices = $container->findTaggedServiceIds(self::ALIAS_PROVIDER_TAG_NAME);
        foreach ($taggedServices as $id => $attributes) {
            $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
            $aliasProviders[$priority][] = new Reference($id);
        }
        if (!empty($aliasProviders)) {
            // sort by priority and flatten
            krsort($aliasProviders);
            $aliasProviders = call_user_func_array('array_merge', $aliasProviders);
        }

        // register
        $resolverDef = $container->getDefinition(self::LOADER_SERVICE);
        foreach ($classProviders as $classProvider) {
            $resolverDef->addMethodCall('addEntityClassProvider', [$classProvider]);
        }
        foreach ($aliasProviders as $aliasProvider) {
            $resolverDef->addMethodCall('addEntityAliasProvider', [$aliasProvider]);
        }
    }
}
