<?php

namespace Oro\Bundle\NotificationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all notification event handlers.
 */
class NotificationHandlerPass implements CompilerPassInterface
{
    private const LOCATOR_SERVICE_KEY = 'oro_notification.handler_locator';
    private const MANAGER_SERVICE_KEY = 'oro_notification.manager';
    private const HANDLER_TAG         = 'notification.handler';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $handlers = [];
        $handlerMap = [];
        $taggedServices = $container->findTaggedServiceIds(self::HANDLER_TAG, true);
        foreach ($taggedServices as $id => $attributes) {
            foreach ($attributes as $tagAttributes) {
                $handlerMap[$id] = new Reference($id);
                $handlers[$tagAttributes['priority'] ?? 0][] = $id;
            }
        }
        if (empty($handlerMap)) {
            return;
        }

        krsort($handlers);
        $handlers = array_merge(...$handlers);

        $container->getDefinition(self::LOCATOR_SERVICE_KEY)
            ->replaceArgument(0, $handlerMap);
        $container->getDefinition(self::MANAGER_SERVICE_KEY)
            ->replaceArgument(0, $handlers);
    }
}
