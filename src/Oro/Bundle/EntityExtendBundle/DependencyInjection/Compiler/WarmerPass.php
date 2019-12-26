<?php

namespace Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers the cache warmers tagged by "oro_entity_extend.cache_warmer".
 * and replace "cache_warmer" service with "oro_entity_extend.cache_warmer_aggregate".
 */
class WarmerPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    private const CACHE_WARMER_AGGREGATE_SERVICE = 'oro_entity_extend.cache_warmer_aggregate';
    private const CACHE_WARMER_SERVICE           = 'cache_warmer';
    private const EXTEND_CACHE_WARMER_SERVICE    = 'oro_entity_extend.cache_warmer';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
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
