<?php

namespace Oro\Bundle\NotificationBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServiceTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all notification event handlers.
 */
class NotificationHandlerPass implements CompilerPassInterface
{
    use TaggedServiceTrait;

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
        foreach ($taggedServices as $id => $tags) {
            $handlerMap[$id] = new Reference($id);
            foreach ($tags as $attributes) {
                $handlers[$this->getPriorityAttribute($attributes)][] = $id;
            }
        }
        if ($handlers) {
            $handlers = $this->sortByPriorityAndFlatten($handlers);
        }

        $container->getDefinition(self::MANAGER_SERVICE_KEY)
            ->replaceArgument(0, $handlers)
            ->replaceArgument(1, ServiceLocatorTagPass::register($container, $handlerMap));
    }
}
