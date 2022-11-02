<?php

namespace Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Collects message processors for {@see \Oro\Component\MessageQueue\Client\MessageProcessorRegistry}
 */
class BuildMessageProcessorRegistryPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $processorRegistryId = 'oro_message_queue.client.message_processor_registry';
        if (!$container->hasDefinition($processorRegistryId)) {
            return;
        }

        $messageProcessors = [];
        $taggedServiceIds = $container->findTaggedServiceIds('oro_message_queue.client.message_processor');
        foreach ($taggedServiceIds as $serviceId => $tagAttributes) {
            $messageProcessors[] = new Reference($serviceId);
        }

        // Adds a last-call message processor for the messages which are not claimed by any processor.
        // Added manually as it cannot be registered with a tag because of empty topic name.
        $messageProcessors[] = new Reference('oro_message_queue.client.noop_message_processor');

        $messageProcessorRegistryDef = $container->getDefinition($processorRegistryId);
        $messageProcessorRegistryDef->replaceArgument(0, $messageProcessors);
    }
}
