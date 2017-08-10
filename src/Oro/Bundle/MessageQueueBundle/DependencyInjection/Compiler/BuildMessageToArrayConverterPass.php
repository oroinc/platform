<?php

namespace Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class BuildMessageToArrayConverterPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        // find converters
        $converters = [];
        $taggedServices = $container->findTaggedServiceIds('oro_message_queue.log.message_to_array_converter');
        foreach ($taggedServices as $id => $attributes) {
            $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
            $converters[$priority][] = new Reference($id);
        }

        // sort by priority and flatten
        krsort($converters);
        $converters = call_user_func_array('array_merge', $converters);

        // register
        $container->getDefinition('oro_message_queue.log.message_to_array_converter')
            ->replaceArgument(0, $converters);
    }
}
