<?php

namespace Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class AddConditionAndActionCompilerPass implements CompilerPassInterface
{
    const CONDITION_TAG = 'oro_workflow.condition';
    const CONDITION_FACTORY_SERVICE = 'oro_workflow.condition_factory';
    const ACTION_TAG = 'oro_workflow.action';
    const ACTION_FACTORY_SERVICE = 'oro_workflow.action_factory';
    const EVENT_DISPATCHER_SERVICE = 'event_dispatcher';

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $this->injectEntityTypesByTag($container, self::CONDITION_FACTORY_SERVICE, self::CONDITION_TAG);
        $this->injectEntityTypesByTag($container, self::ACTION_FACTORY_SERVICE, self::ACTION_TAG, true);
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $serviceId
     * @param string           $tagName
     * @param bool             $addDispatcher
     */
    protected function injectEntityTypesByTag(ContainerBuilder $container, $serviceId, $tagName, $addDispatcher = false)
    {
        $types = [];

        foreach ($container->findTaggedServiceIds($tagName) as $id => $attributes) {
            $definition = $container->getDefinition($id);
            $definition->setScope(ContainerInterface::SCOPE_PROTOTYPE);
            if ($addDispatcher) {
                $definition->addMethodCall('setDispatcher', [new Reference(self::EVENT_DISPATCHER_SERVICE)]);
            }

            foreach ($attributes as $eachTag) {
                if (!empty($eachTag['alias'])) {
                    $aliases = explode('|', $eachTag['alias']);
                } else {
                    $aliases = [$id];
                }
                foreach ($aliases as $alias) {
                    $types[$alias] = $id;
                }
            }
        }

        $definition = $container->getDefinition($serviceId);
        $definition->replaceArgument(1, $types);
    }
}
