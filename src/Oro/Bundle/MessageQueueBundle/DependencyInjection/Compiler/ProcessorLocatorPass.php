<?php

namespace Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all processors inside service locator.
 */
class ProcessorLocatorPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $processors = [
            'oro_message_queue.client.delegate_message_processor' => new Reference(
                'oro_message_queue.client.delegate_message_processor'
            )
        ];

        $ids = array_keys($container->findTaggedServiceIds('oro_message_queue.client.message_processor', true));
        foreach ($ids as $id) {
            $processors[$id] = new Reference($id);
        }

        $container->getDefinition('oro_message_queue.processor_locator')
            ->replaceArgument(0, $processors);
    }
}
