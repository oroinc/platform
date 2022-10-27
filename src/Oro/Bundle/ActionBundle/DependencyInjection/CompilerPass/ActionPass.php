<?php

namespace Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass;

use Oro\Component\Action\Action\EventDispatcherAwareActionInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Sets event dispatcher to the action instances.
 */
class ActionPass extends AbstractPass
{
    const ACTION_TAG = 'oro_action.action';
    const ACTION_FACTORY_SERVICE_ID = 'oro_action.action_factory';

    const EVENT_DISPATCHER_SERVICE = 'event_dispatcher';

    /** @var ParameterBagInterface */
    private $parameterBag;

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->parameterBag = $container->getParameterBag();
        $this->processTypes($container, self::ACTION_FACTORY_SERVICE_ID, self::ACTION_TAG);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareDefinition(Definition $definition)
    {
        parent::prepareDefinition($definition);

        $className = $this->parameterBag->resolveValue($definition->getClass());
        if (\is_subclass_of($className, EventDispatcherAwareActionInterface::class)) {
            $definition->addMethodCall('setDispatcher', [new Reference(self::EVENT_DISPATCHER_SERVICE)]);
        }
    }
}
