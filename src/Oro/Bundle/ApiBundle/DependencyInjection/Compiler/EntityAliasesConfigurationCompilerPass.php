<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class EntityAliasesConfigurationCompilerPass implements CompilerPassInterface
{
    const LOADER_SERVICE          = 'oro_api.entity_alias_loader';
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
        $classProviders = $this->getClassProviders($container);
        $aliasProviders = $this->getAliasProviders($container);

        // register
        $resolverDef = $container->getDefinition(self::LOADER_SERVICE);
        foreach ($classProviders as $classProvider) {
            $resolverDef->addMethodCall('addEntityClassProvider', [$classProvider]);
        }
        foreach ($aliasProviders as $aliasProvider) {
            $resolverDef->addMethodCall('addEntityAliasProvider', [$aliasProvider]);
        }
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return string[]
     */
    protected function getClassProviders(ContainerBuilder $container)
    {
        $classProviders = [];
        $taggedServices = $container->findTaggedServiceIds(self::CLASS_PROVIDER_TAG_NAME);
        foreach ($taggedServices as $id => $attributes) {
            $classProviders[] = new Reference($id);
        }

        return $classProviders;
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return string[]
     */
    protected function getAliasProviders(ContainerBuilder $container)
    {
        $aliasProviders = [];
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

        return $aliasProviders;
    }
}
