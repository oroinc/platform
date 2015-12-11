<?php

namespace Oro\Bundle\DistributionBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RoutingOptionsResolverPass implements CompilerPassInterface
{
    const CHAIN_RESOLVER_SERVICE = 'oro_distribution.routing_options_resolver';
    const RESOLVER_TAG_NAME = 'routing.options_resolver';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::CHAIN_RESOLVER_SERVICE)) {
            return;
        }

        // find resolvers
        $resolvers      = [];
        $taggedServices = $container->findTaggedServiceIds(self::RESOLVER_TAG_NAME);
        foreach ($taggedServices as $id => $attributes) {
            $priority               = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
            $resolvers[$priority][] = new Reference($id);
        }
        if (empty($resolvers)) {
            return;
        }

        // sort by priority and flatten
        krsort($resolvers);
        $resolvers = call_user_func_array('array_merge', $resolvers);

        // register
        $chainResolverDef = $container->getDefinition(self::CHAIN_RESOLVER_SERVICE);
        foreach ($resolvers as $resolver) {
            $chainResolverDef->addMethodCall('addResolver', [$resolver]);
        }
    }
}
