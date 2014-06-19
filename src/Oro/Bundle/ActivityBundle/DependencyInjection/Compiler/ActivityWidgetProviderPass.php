<?php

namespace Oro\Bundle\ActivityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ActivityWidgetProviderPass implements CompilerPassInterface
{
    const SERVICE_ID = 'oro_activity.widget_provider';
    const TAG_NAME   = 'oro_activity.widget_provider';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::SERVICE_ID)) {
            return;
        }

        // find providers
        $providers      = [];
        $taggedServices = $container->findTaggedServiceIds(self::TAG_NAME);
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
        $serviceDef = $container->getDefinition(self::SERVICE_ID);
        foreach ($providers as $provider) {
            $serviceDef->addMethodCall('addProvider', [$provider]);
        }
    }
}
