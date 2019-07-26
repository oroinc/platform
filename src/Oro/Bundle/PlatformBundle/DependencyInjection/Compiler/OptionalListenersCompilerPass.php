<?php

namespace Oro\Bundle\PlatformBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Collects all optional listeners in order to transfer them to the listener manager.
 */
class OptionalListenersCompilerPass implements CompilerPassInterface
{
    const KERNEL_LISTENER_TAG   = 'kernel.event_listener';
    const KERNEL_SUBSCRIBER_TAG = 'kernel.event_subscriber';

    const DOCTRINE_ORM_LISTENER_TAG = 'doctrine.orm.entity_listener';
    const DOCTRINE_LISTENER_TAG   = 'doctrine.event_listener';
    const DOCTRINE_SUBSCRIBER_TAG = 'doctrine.event_subscriber';

    const OPTIONAL_LISTENER_MANAGER = 'oro_platform.optional_listeners.manager';
    const OPTIONAL_LISTENER_INTERFACE = 'Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $listeners = array_keys(
            array_merge(
                $container->findTaggedServiceIds(self::KERNEL_LISTENER_TAG),
                $container->findTaggedServiceIds(self::KERNEL_SUBSCRIBER_TAG),
                $container->findTaggedServiceIds(self::DOCTRINE_ORM_LISTENER_TAG),
                $container->findTaggedServiceIds(self::DOCTRINE_LISTENER_TAG),
                $container->findTaggedServiceIds(self::DOCTRINE_SUBSCRIBER_TAG)
            )
        );

        $optionalListeners = [];
        $parameterBag = $container->getParameterBag();
        foreach ($listeners as $listener) {
            $listenerDefinition = $container->getDefinition($listener);
            $className = $parameterBag->resolveValue($listenerDefinition->getClass());

            if (\is_subclass_of($className, self::OPTIONAL_LISTENER_INTERFACE)) {
                $optionalListeners[] = $listener;
                $listenerDefinition->setPublic(true);
            }
        }

        $definition = $container->getDefinition(self::OPTIONAL_LISTENER_MANAGER);
        $definition->replaceArgument(0, $optionalListeners);
    }
}
