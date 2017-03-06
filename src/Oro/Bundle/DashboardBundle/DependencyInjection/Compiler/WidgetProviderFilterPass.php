<?php

namespace Oro\Bundle\DashboardBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class WidgetProviderFilterPass implements CompilerPassInterface
{
    const TAG                   = 'oro_dashboard.widget_provider.filter';
    const MANAGER_SERVICE_ID    = 'oro_dashboard.widget_provider.filter_manager';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(static::MANAGER_SERVICE_ID)) {
            return;
        }

        $definition = $container->getDefinition(static::MANAGER_SERVICE_ID);
        $taggedServices = $container->findTaggedServiceIds(self::TAG);

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall(
                'addFilter',
                [new Reference($id)]
            );
        }
    }
}
