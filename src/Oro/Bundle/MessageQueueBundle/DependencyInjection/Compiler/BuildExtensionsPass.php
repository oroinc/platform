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

        $groupByPriority = [];
        foreach ($tags as $serviceId => $tagAttributes) {
            foreach ($tagAttributes as $tagAttribute) {
                $priority = isset($tagAttribute['priority']) ? (int) $tagAttribute['priority'] : 0;

                $groupByPriority[$priority][] = new Reference($serviceId);
            }
        }

        ksort($groupByPriority);

        $flatExtensions = [];
        foreach ($groupByPriority as $extension) {
            $flatExtensions = array_merge($flatExtensions, $extension);
        }

        $container->getDefinition('oro_message_queue.consumption.extensions')->replaceArgument(0, $flatExtensions);
    }
}
