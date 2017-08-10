<?php
namespace Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Collects consumption and job extensions.
 */
class BuildExtensionsPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->processExtensions(
            $container,
            'oro_message_queue.consumption.extension',
            'oro_message_queue.consumption.extensions'
        );

        $this->processExtensions(
            $container,
            'oro_message_queue.job.extension',
            'oro_message_queue.job.extensions'
        );
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $tag
     * @param string           $targetService
     */
    protected function processExtensions(ContainerBuilder $container, $tag, $targetService)
    {
        $tags = $container->findTaggedServiceIds($tag);

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

        $container->getDefinition($targetService)->replaceArgument(0, $flatExtensions);
    }
}
