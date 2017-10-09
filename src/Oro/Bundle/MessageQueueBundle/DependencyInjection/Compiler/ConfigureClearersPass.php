<?php

namespace Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ConfigureClearersPass implements CompilerPassInterface
{
    const EXTENSION_SERVICE_ID = 'oro_message_queue.consumption.container_reset_extension';
    const CLEARER_TAG          = 'oro_message_queue.consumption.clearer';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        // find clearers
        $clearers = [];
        $taggedServices = $container->findTaggedServiceIds(self::CLEARER_TAG);
        foreach ($taggedServices as $id => $attributes) {
            $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
            $clearers[$priority][] = new Reference($id);
        }
        if (empty($clearers)) {
            return;
        }

        // sort by priority and flatten
        krsort($clearers);
        $clearers = call_user_func_array('array_merge', $clearers);

        // register
        $container->getDefinition(self::EXTENSION_SERVICE_ID)
            ->replaceArgument(0, $clearers);
    }
}
