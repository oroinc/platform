<?php

namespace Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class WarmerPass implements CompilerPassInterface
{
    const EXTEND_CACHE_WARMER_SERVICE  = 'oro_entity_extend.cache_warmer';
    const EXTEND_CACHE_WARMER_TAG_NAME = 'oro_entity_extend.warmer';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::EXTEND_CACHE_WARMER_SERVICE)) {
            return;
        }

        $taggedServices = $container->findTaggedServiceIds(self::EXTEND_CACHE_WARMER_TAG_NAME);
        if (!$taggedServices) {
            return;
        }

        // load
        $warmers = [];
        foreach ($taggedServices as $id => $attributes) {
            $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
            $warmers[$priority][] = new Reference($id);
        }

        // sort by priority and flatten
        krsort($warmers);
        $warmers = call_user_func_array('array_merge', $warmers);

        // register
        $cacheWarmerDef = $container->getDefinition(self::EXTEND_CACHE_WARMER_SERVICE);
        foreach ($warmers as $warmer) {
            $cacheWarmerDef->addMethodCall('addWarmer', [$warmer]);
        }

        // mark the decorated cache warmer as lazy
        $container->getDefinition((string)$cacheWarmerDef->getArgument(0))->setLazy(true);
    }
}
