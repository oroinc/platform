<?php

namespace Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers the cache warmers tagged by "oro_entity_extend.cache_warmer".
 * and replace "cache_warmer" service with "oro_entity_extend.cache_warmer_aggregate".
 */
class WarmerPass implements CompilerPassInterface
{
    const CACHE_WARMER_AGGREGATE_SERVICE = 'oro_entity_extend.cache_warmer_aggregate';
    const CACHE_WARMER_SERVICE           = 'cache_warmer';
    const EXTEND_CACHE_WARMER_SERVICE    = 'oro_entity_extend.cache_warmer';
    const EXTEND_CACHE_WARMER_TAG_NAME   = 'oro_entity_extend.warmer';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        // load
        $warmers = [];
        $taggedServices = $container->findTaggedServiceIds(self::EXTEND_CACHE_WARMER_TAG_NAME);
        foreach ($taggedServices as $id => $attributes) {
            $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
            $warmers[$priority][] = new Reference($id);
        }

        // sort by priority and flatten
        krsort($warmers);
        $warmers = call_user_func_array('array_merge', $warmers);

        // register
        $container->getDefinition(self::EXTEND_CACHE_WARMER_SERVICE)
            ->replaceArgument(0, $warmers);

        // replace "cache_warmer" service with "oro_entity_extend.cache_warmer_aggregate"
        $cacheWarmerAggregateDefinition = $container->getDefinition(self::CACHE_WARMER_AGGREGATE_SERVICE);
        $cacheWarmerDefinition = $container->getDefinition(self::CACHE_WARMER_SERVICE);
        $cacheWarmerNewServiceId = 'oro_entity_extend.cache_warmer.default';
        $container->removeDefinition(self::CACHE_WARMER_AGGREGATE_SERVICE);
        $container->removeDefinition(self::CACHE_WARMER_SERVICE);
        $container->setDefinition($cacheWarmerNewServiceId, $cacheWarmerDefinition);
        $cacheWarmerAggregateDefinition
            ->replaceArgument(0, new Reference($this->addServiceLink($container, $cacheWarmerNewServiceId)))
            ->replaceArgument(1, new Reference($this->addServiceLink($container, self::EXTEND_CACHE_WARMER_SERVICE)));
        $container->setDefinition(self::CACHE_WARMER_SERVICE, $cacheWarmerAggregateDefinition);
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $serviceId
     *
     * @return string
     */
    private function addServiceLink(ContainerBuilder $container, $serviceId)
    {
        // register the link to the config manager service
        $linkServiceId = $serviceId . '.link';
        $linkDefinition = new Definition(
            ServiceLink::class,
            [new Reference('service_container'), $serviceId]
        );
        $linkDefinition->setPublic(false);
        $container->setDefinition($linkServiceId, $linkDefinition);

        return $linkServiceId;
    }
}
