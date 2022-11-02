<?php

namespace Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ResettableExtensionInterface;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ResettableExtensionWrapper;
use Oro\Component\DependencyInjection\Compiler\TaggedServiceTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Collects consumption extensions.
 */
class BuildExtensionsPass implements CompilerPassInterface
{
    use TaggedServiceTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->processConsumptionExtensions($container);
    }

    private function processConsumptionExtensions(ContainerBuilder $container): void
    {
        $extensions = [];
        $taggedServices = $container->findTaggedServiceIds('oro_message_queue.consumption.extension');
        foreach ($taggedServices as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                $priority = $this->getPriorityAttribute($attributes);
                $persistent = $this->getAttribute($attributes, 'persistent', false);
                $extensions[$priority][] = [$serviceId, $persistent];
            }
        }
        if (empty($extensions)) {
            return;
        }

        $extensions = $this->sortByPriorityAndFlatten($extensions);

        $extensionReferences = [];
        foreach ($extensions as [$serviceId, $persistent]) {
            if (!$persistent) {
                $service = $container->getDefinition($serviceId);
                $serviceClass = $service->getClass();
                if (str_starts_with($serviceClass, '%')) {
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
