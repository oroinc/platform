<?php

namespace Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ResettableExtensionInterface;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ResettableExtensionWrapper;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
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
        $this->processConsumptionExtensions($container);
        $this->processJobExtensions($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function processConsumptionExtensions(ContainerBuilder $container)
    {
        $extensions = [];
        $taggedServices = $container->findTaggedServiceIds('oro_message_queue.consumption.extension');
        foreach ($taggedServices as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                $priority = 0;
                if (isset($attributes['priority'])) {
                    $priority = (int)$attributes['priority'];
                }
                $persistent = false;
                if (isset($attributes['persistent'])) {
                    $persistent = (bool)$attributes['persistent'];
                }

                $extensions[$priority][] = [$serviceId, $persistent];
            }
        }
        if (empty($extensions)) {
            return;
        }

        // sort by priority and flatten
        krsort($extensions);
        $extensions = call_user_func_array('array_merge', $extensions);

        $extensionReferences = [];
        foreach ($extensions as list($serviceId, $persistent)) {
            if (!$persistent) {
                $service = $container->getDefinition($serviceId);
                $serviceClass = $service->getClass();
                if (0 === strpos($serviceClass, '%')) {
                    $serviceClass = $container->getParameter(substr($serviceClass, 1, -1));
                }
                if (!is_a($serviceClass, ResettableExtensionInterface::class, true)) {
                    $service->setPublic(true);

                    $resettableWrapper = new Definition(
                        ResettableExtensionWrapper::class,
                        [new Reference('service_container'), $serviceId]
                    );
                    $resettableWrapper->setPublic(false);

                    $serviceId .= '.resettable_wrapper';
                    $container->setDefinition($serviceId, $resettableWrapper);
                }
            }
            $extensionReferences[] = new Reference($serviceId);
        }

        $container->getDefinition('oro_message_queue.consumption.extensions')
            ->replaceArgument(0, $extensionReferences);
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function processJobExtensions(ContainerBuilder $container)
    {
        $extensions = [];
        $taggedServices = $container->findTaggedServiceIds('oro_message_queue.job.extension');
        foreach ($taggedServices as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                $priority = 0;
                if (isset($attributes['priority'])) {
                    $priority = (int)$attributes['priority'];
                }

                $extensions[$priority][] = new Reference($serviceId);
            }
        }
        if (empty($extensions)) {
            return;
        }

        // sort by priority and flatten
        krsort($extensions);
        $extensions = call_user_func_array('array_merge', $extensions);

        $container->getDefinition('oro_message_queue.job.extensions')
            ->replaceArgument(0, $extensions);
    }
}
