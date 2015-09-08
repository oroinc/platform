<?php

namespace Oro\Bundle\NavigationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ChainBreadcrumbManagerPass implements CompilerPassInterface
{
    const TAG = 'oro_breadcrumbs.provider';
    const PROVIDER_SERVICE_ID = 'oro_navigation.chain_breadcrumb_manager';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::PROVIDER_SERVICE_ID)) {
            return;
        }

        // find providers
        $providers = [];
        $taggedServices = $container->findTaggedServiceIds(self::TAG);
        foreach ($taggedServices as $id => $attributes) {
            $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
            $providers[$priority][] = new Reference($id);
        }
        if (0 === count($providers)) {
            return;
        }

        // sort by priority and flatten
        ksort($providers);
        $providers = call_user_func_array('array_merge', $providers);

        // register
        $serviceDef = $container->getDefinition(self::PROVIDER_SERVICE_ID);
        foreach ($providers as $provider) {
            $serviceDef->addMethodCall('addManager', [$provider]);
        }
    }
}
