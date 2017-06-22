<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Exception\RuntimeException;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionFactory;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\MassActionInterface;

class MassActionFactory extends ActionFactory
{
    /**
     * Creates an action object.
     *
     * @param string $actionName
     * @param array  $actionConfig
     *
     * @return MassActionInterface
     *
     * @throws RuntimeException if the requested action has invalid configuration
     */
    public function createAction($actionName, array $actionConfig)
    {
        $action = parent::createAction($actionName, $actionConfig);
        if (!$action instanceof MassActionInterface) {
            throw new RuntimeException(
                sprintf(
                    'An action should be an instance of "%s", got "%s".',
                    MassActionInterface::class,
                    get_class($action)
                )
            );
        }

        return $action;
    }
}
