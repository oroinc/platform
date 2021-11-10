<?php

namespace Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Collects message processors metadata for {@see \Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry}
 */
class BuildDestinationMetaRegistryPass implements CompilerPassInterface
{
    use MessageProcessorsMetadataTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $destinationMetaRegistryId = 'oro_message_queue.client.meta.destination_meta_registry';
        if (!$container->hasDefinition($destinationMetaRegistryId)) {
            return;
        }

        $messageProcessorsMetadata = $this->findMessageProcessorsMetadata(
            $container,
            'oro_message_queue.client.message_processor'
        );

        $messageProcessorsByQueue = [];
        foreach ($messageProcessorsMetadata as [$serviceId, $topicName, $queueName]) {
            $messageProcessorsByQueue[$queueName][] = $serviceId;
        }

        $container
            ->getDefinition($destinationMetaRegistryId)
            ->setArgument('$messageProcessorsByQueue', $messageProcessorsByQueue);
    }
}
