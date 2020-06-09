<?php

namespace Oro\Bundle\NotificationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Subscribe notification manager to events.
 */
class EventsCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $notificationManager = $container->findDefinition('oro_notification.manager');

        foreach ($container->getParameter('oro_notification.events') as $eventName) {
            $notificationManager->addTag(
                'kernel.event_listener',
                [
                    'event' => $eventName,
                    'method' => 'process',
                ]
            );
        }
    }
}
