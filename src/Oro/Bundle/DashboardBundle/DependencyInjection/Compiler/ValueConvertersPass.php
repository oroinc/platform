<?php

namespace Oro\Bundle\DashboardBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ValueConvertersPass implements CompilerPassInterface
{
    const TAG = 'oro_dashboard.value.converter';
    const PROVIDER_SERVICE_ID = 'oro_dashboard.widget_config_value.provider';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::PROVIDER_SERVICE_ID)) {
            return;
        }
        $serviceDef = $container->getDefinition(self::PROVIDER_SERVICE_ID);

        // find converters
        $taggedServices = $container->findTaggedServiceIds(self::TAG);
        foreach ($taggedServices as $id => $attributes) {
            $serviceDef->addMethodCall('addConverter', [$attributes[0]['form_type'], new Reference($id)]);
        }
    }
}
