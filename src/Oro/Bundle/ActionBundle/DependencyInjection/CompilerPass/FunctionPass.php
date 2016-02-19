<?php

namespace Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class FunctionPass extends AbstractPass
{
    const FUNCTION_TAG = 'oro_action.function';
    const FUNCTION_FACTORY_SERVICE_ID = 'oro_action.function_factory';

    const EVENT_DISPATCHER_SERVICE = 'event_dispatcher';
    const EVENT_DISPATCHER_AWARE_ACTION = 'Oro\Component\ConfigExpression\Action\EventDispatcherAwareActionInterface';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->processTypes($container, self::FUNCTION_FACTORY_SERVICE_ID, self::FUNCTION_TAG);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareDefinition(Definition $definition)
    {
        parent::prepareDefinition($definition);

        $reflection = new \ReflectionClass($definition->getClass());
        if ($reflection->implementsInterface(self::EVENT_DISPATCHER_AWARE_ACTION)) {
            $definition->addMethodCall('setDispatcher', [new Reference(self::EVENT_DISPATCHER_SERVICE)]);
        }
    }
}
