<?php

namespace Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\PriorityTaggedLocatorTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all processors inside service locator.
 */
class ProcessorLocatorPass implements CompilerPassInterface
{
    use PriorityTaggedLocatorTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $processors = $this->findAndSortTaggedServicesWithOptionalNameAttribute(
            'oro_message_queue.client.message_processor',
            $container
        );
        $processors['oro_message_queue.client.delegate_message_processor'] = new Reference(
            'oro_message_queue.client.delegate_message_processor'
        );

        $container->getDefinition('oro_message_queue.processor_locator')
            ->replaceArgument(0, $processors);
    }
}
