<?php

namespace Oro\Bundle\DataGridBundle\Extension\Action;

use Oro\Bundle\DataGridBundle\Exception\RuntimeException;
use Oro\Bundle\DataGridBundle\Extension\Action\Actions\ActionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ActionFactory
{
    const ACTION_TYPE_KEY = 'type';

    /** @var ContainerInterface */
    protected $container;

    /** @var array */
    protected $actions = [];

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Registers a service implements the given action type.
     * The service must implement ActionInterface.
     * @see \Oro\Bundle\DataGridBundle\Extension\Action\Actions\ActionInterface
     *
     * @param string $type
     * @param string $serviceId
     */
    public function registerAction($type, $serviceId)
    {
        $this->actions[$type] = $serviceId;
    }

    /**
     * Creates an action object.
     *
     * @param string $actionName
     * @param array  $actionConfig
     *
     * @return ActionInterface
     *
     * @throws RuntimeException if the requested action has invalid configuration
     */
    public function createAction($actionName, array $actionConfig)
    {
        $config = ActionConfiguration::createNamed($actionName, $actionConfig);
        if (!$config->offsetExists(self::ACTION_TYPE_KEY)) {
            throw new RuntimeException(
                sprintf(
                    'The "%s" option must be defined. Action: %s.',
                    self::ACTION_TYPE_KEY,
                    $actionName
                )
            );
        }

        $type = $config->offsetGet(self::ACTION_TYPE_KEY);
        if (!isset($this->actions[$type])) {
            throw new RuntimeException(
                sprintf(
                    'Unknown action type "%s". Action: %s.',
                    $type,
                    $actionName
                )
            );
        }

        $action = $this->container->get($this->actions[$type]);
        if (!$action instanceof ActionInterface) {
            throw new RuntimeException(
                sprintf(
                    'An action should be an instance of "%s", got "%s".',
                    ActionInterface::class,
                    get_class($action)
                )
            );
        }

        $action->setOptions($config);

        return $action;
    }
}
