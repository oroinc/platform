<?php

namespace Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler;

use Oro\Bundle\WorkflowBundle\Migrations\Data\ORM\LoadWorkflowNotificationEvents;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds the transition event event listener to notification manager.
 */
class EventsCompilerPass implements CompilerPassInterface
{
    const SERVICE_KEY    = 'oro_notification.manager';
    const DISPATCHER_KEY = 'event_dispatcher';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::SERVICE_KEY)) {
            return;
        }

        $call = [LoadWorkflowNotificationEvents::TRANSIT_EVENT, [self::SERVICE_KEY, 'process']];
        $dispatcher = $container->findDefinition(self::DISPATCHER_KEY);

        if (in_array($call, $dispatcher->getMethodCalls(), true)) {
            return;
        }

        $dispatcher->addMethodCall('addListener', [
            LoadWorkflowNotificationEvents::TRANSIT_EVENT,
            [new ServiceClosureArgument(new Reference(self::SERVICE_KEY)), 'process'],
            0
        ]);
    }
}
