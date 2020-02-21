<?php

namespace Oro\Bundle\PlatformBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Increases priority of lexik maintenance listener to make it execute before the listeners which require extend cache.
 */
class MaintenanceListenerPriorityCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $def = $container->getDefinition('lexik_maintenance.listener');

        if (!$def->hasTag('kernel.event_listener')) {
            return;
        }

        $tags = $def->getTags();
        foreach ($tags['kernel.event_listener'] as $key => $tagAttributes) {
            if ($tagAttributes['event'] === 'kernel.request') {
                $tags['kernel.event_listener'][$key]['priority'] = 512;
            }
        }
        $def->setTags($tags);
    }
}
