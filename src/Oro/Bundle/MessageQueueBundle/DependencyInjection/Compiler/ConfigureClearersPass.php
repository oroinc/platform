<?php

namespace Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers all MQ consumption clearers.
 */
class ConfigureClearersPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('oro_message_queue.consumption.container_reset_extension')
            ->replaceArgument(
                0,
                $this->findAndSortTaggedServices('oro_message_queue.consumption.clearer', $container)
            );
    }
}
