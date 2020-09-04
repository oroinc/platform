<?php

namespace Oro\Bundle\NotificationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Set registered notification events to the notification event dispatcher decorator, to not register them twice.
 */
class SetRegisteredNotificationEventsCompilerPass implements CompilerPassInterface
{
    private const ORO_NOTIFICATION_MANAGER = 'oro_notification.manager';
    private const ORO_NOTIFICATION_EVENT_DISPATCHER_DECORATOR = 'oro_notification.event_dispatcher_decorator';
    private const EVENT_DISPATCHER = 'event_dispatcher';

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function process(ContainerBuilder $container)
    {
        $eventDispatcherDecorator = $container->findDefinition(self::ORO_NOTIFICATION_EVENT_DISPATCHER_DECORATOR);
        $dispatcher = $container->findDefinition(self::EVENT_DISPATCHER);
        $registeredEvents = [];
        foreach ($dispatcher->getMethodCalls() as $methodCall) {
            if (array_key_exists(1, $methodCall[1])) {
                [$eventName, $callable] = $methodCall[1];

                if (!is_array($callable) || $callable[1] !== 'process') {
                    continue;
                }

                $callee = $callable[0];
                if ($callee instanceof ServiceClosureArgument &&
                    (string)$callee->getValues()[0] === self::ORO_NOTIFICATION_MANAGER) {
                    $registeredEvents[] = $eventName;
                    continue;
                }
                if ($callee === self::ORO_NOTIFICATION_MANAGER) {
                    $registeredEvents[] = $eventName;
                    continue;
                }
                if (method_exists($callee, '__toString') && (string)$callee === self::ORO_NOTIFICATION_MANAGER) {
                    $registeredEvents[] = $eventName;
                    continue;
                }
            }
        }
        if (!empty($registeredEvents)) {
            $eventDispatcherDecorator->addMethodCall('setRegisteredNotificationEvents', [$registeredEvents]);
        }
    }
}
