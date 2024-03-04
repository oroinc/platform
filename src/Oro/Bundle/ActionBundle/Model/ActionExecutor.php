<?php

namespace Oro\Bundle\ActionBundle\Model;

use Oro\Component\Action\Action\ActionFactoryInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

/**
 * Utility service to execute actions and action groups.
 */
class ActionExecutor
{
    public function __construct(
        private ActionFactoryInterface $actionRegistry,
        private ActionGroupRegistry $actionGroupRegistry
    ) {
    }

    public function executeAction(string $actionName, array $data = []): ActionData
    {
        $action = $this->actionRegistry->create($actionName, $this->prepareOptions($data));

        $context = new ActionData($data);
        $action->execute($context);

        return $context;
    }

    public function executeActionGroup(string $actionGroupName, array $data = []): ActionData
    {
        return $this->actionGroupRegistry->get($actionGroupName)->execute(new ActionData($data));
    }

    private function prepareOptions(array $data): array
    {
        $options = [];
        foreach ($data as $key => $value) {
            $options[$key] = $value instanceof PropertyPath ? $value : new PropertyPath($key);
        }

        return $options;
    }
}
