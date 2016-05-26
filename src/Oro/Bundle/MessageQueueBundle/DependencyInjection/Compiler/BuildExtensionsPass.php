<?php
namespace Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class BuildExtensionsPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $tags = $container->findTaggedServiceIds('oro_message_queue.consumption.extension');
        $extensions = [];
        foreach ($tags as $serviceId => $tagAttributes) {
            $extensions[] = new Reference($serviceId);
        }

        $container->getDefinition('oro_message_queue.consumption.extensions')->replaceArgument(0, $extensions);
    }
}
