<?php
/**
 * Created by PhpStorm.
 * User: Matey
 * Date: 02.06.2016
 * Time: 15:57
 */

namespace Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class WorkflowChangesEventsCompilerPass implements CompilerPassInterface
{
    const CHANGES_LISTENER_TAG = 'oro_workflow.changes.listener';
    const CHANGES_SUBSCRIBER_TAG = 'oro_workflow.changes.subscriber';
    const CHANGES_DISPATCHER_SERVICE = 'oro_workflow.changes.event.dispatcher';

    /** {@inheritdoc} */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::CHANGES_DISPATCHER_SERVICE)) {
            return;
        }

        $dispatcherDefinition = $container->getDefinition(self::CHANGES_DISPATCHER_SERVICE);

        foreach ($container->findTaggedServiceIds(self::CHANGES_LISTENER_TAG) as $service => $tag) {
            foreach ($tag as $attributes) {
                if (!array_key_exists('event', $attributes)) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'An "event" attribute for tag `%s` in service `%s` must be defined',
                            self::CHANGES_LISTENER_TAG,
                            $service
                        )
                    );
                }
                $dispatcherDefinition->addMethodCall(
                    'addListener', [
                        $attributes['event'],
                        new Reference($service),
                        array_key_exists('priority', $attributes) ? $attributes['priority'] : 0
                    ]
                );
            }
        }

        foreach ($container->findTaggedServiceIds(self::CHANGES_SUBSCRIBER_TAG) as $service => $tag) {
            $dispatcherDefinition->addMethodCall('addSubscriber', [new Reference($service)]);
        }
    }
}