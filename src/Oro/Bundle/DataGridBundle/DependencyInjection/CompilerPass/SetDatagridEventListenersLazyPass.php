<?php

namespace Oro\Bundle\DataGridBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Marks event listener services for all data grids as "lazy"
 * to prevent loading of services used by them on each request in the dev mode.
 * The loading of all event listeners is triggered by Symfony's EventDataCollector.
 */
class SetDatagridEventListenersLazyPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $datagridEventListeners = [];
        $eventListeners         = $container->findTaggedServiceIds('kernel.event_listener');
        foreach ($eventListeners as $serviceId => $tags) {
            foreach ($tags as $tag) {
                if (isset($tag['event']) && 0 === strpos($tag['event'], 'oro_datagrid')) {
                    $datagridEventListeners[] = $serviceId;
                }
            }
        }
        $datagridEventListeners = array_unique($datagridEventListeners);
        foreach ($datagridEventListeners as $serviceId) {
            if ($container->hasDefinition($serviceId)) {
                $serviceDef = $container->getDefinition($serviceId);
                if (!$serviceDef->isLazy()) {
                    $serviceDef->setLazy(true);
                }
            }
        }
    }
}
