<?php

namespace Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ResettableExtensionInterface;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ResettableExtensionWrapper;

class BuildExtensionsPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
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

        ksort($extensions);

        $flatExtensions = [];
        foreach ($extensions as $extension) {
            $flatExtensions = array_merge($flatExtensions, $extension);
        }

        $extensionReferences = [];
        foreach ($flatExtensions as $extension) {
            list($serviceId, $persistent) = $extension;
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
}
