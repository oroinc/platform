<?php

namespace Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class AddConditionAndActionCompilerPass implements CompilerPassInterface
{
    const ACTION_TAG                    = 'oro_workflow.action';
    const ACTION_FACTORY_SERVICE        = 'oro_workflow.action_factory';
    const EXPRESSION_TAG                = 'oro_workflow.condition';
    const EXTENSION_SERVICE             = 'oro_workflow.expression.extension';
    const EVENT_DISPATCHER_SERVICE      = 'event_dispatcher';
    const EVENT_DISPATCHER_AWARE_ACTION = 'Oro\Bundle\WorkflowBundle\Model\Action\EventDispatcherAwareActionInterface';

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $this->injectEntityTypesByTag($container, self::EXTENSION_SERVICE, self::EXPRESSION_TAG);
        $this->injectEntityTypesByTag($container, self::ACTION_FACTORY_SERVICE, self::ACTION_TAG);
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $serviceId
     * @param string           $tagName
     */
    protected function injectEntityTypesByTag(ContainerBuilder $container, $serviceId, $tagName)
    {
        $types = [];

        foreach ($container->findTaggedServiceIds($tagName) as $id => $attributes) {
            $definition = $container->getDefinition($id);
            $definition->setScope(ContainerInterface::SCOPE_PROTOTYPE)->setPublic(false);

            $className = $definition->getClass();
            $refClass = new \ReflectionClass($className);
            if ($refClass->implementsInterface(self::EVENT_DISPATCHER_AWARE_ACTION)) {
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
