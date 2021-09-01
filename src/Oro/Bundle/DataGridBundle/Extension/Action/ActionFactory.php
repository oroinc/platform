<?php

namespace Oro\Bundle\DataGridBundle\Extension\Action;

use Oro\Bundle\DataGridBundle\Exception\RuntimeException;
use Oro\Bundle\DataGridBundle\Extension\Action\Actions\ActionInterface;
use Psr\Container\ContainerInterface;

/**
 * The factory for datagrid actions.
 */
class ActionFactory
{
    private const ACTION_TYPE_KEY = 'type';

    /** @var ContainerInterface */
    private $actionContainer;

    public function __construct(ContainerInterface $actionContainer)
    {
        $this->actionContainer = $actionContainer;
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

        $type = (string) $config->offsetGet(self::ACTION_TYPE_KEY);
        if (!$this->actionContainer->has($type)) {
            throw new RuntimeException(sprintf('Unknown action type "%s". Action: %s.', $type, $actionName));
        }

        $action = $this->actionContainer->get($type);
        if (!$action instanceof ActionInterface) {
            throw new RuntimeException(sprintf(
                'An action should be an instance of "%s", got "%s".',
                ActionInterface::class,
                get_class($action)
            ));
        }

        $action->setOptions($config);

        return $action;
    }
}
