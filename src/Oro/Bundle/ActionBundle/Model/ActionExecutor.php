<?php

namespace Oro\Bundle\ActionBundle\Model;

use Oro\Component\Action\Action\ActionFactoryInterface;
use Oro\Component\ConfigExpression\ExpressionFactory;
use Symfony\Component\PropertyAccess\PropertyPath;

/**
 * Utility service to execute actions and action groups.
 */
class ActionExecutor
{
    public function __construct(
        private ActionFactoryInterface $actionFactory,
        private ActionGroupRegistry $actionGroupRegistry,
        private ExpressionFactory $expressionFactory
    ) {
    }

    public function executeAction(string $actionName, array $data = []): mixed
    {
        $action = $this->actionFactory->create($actionName, $this->prepareOptions($data));

        $context = new ActionData($data);
        $action->execute($context);

        return $context;
    }

    public function executeActionGroup(string $actionGroupName, array $data = []): ActionData
    {
        return $this->actionGroupRegistry->get($actionGroupName)->execute(new ActionData($data));
    }

    public function evaluateExpression(
        string $expressionName,
        array $data = [],
        ?\ArrayAccess $errors = null,
        ?string $message = null
    ): bool {
        $options = $this->prepareOptions($data);
        $expression = $this->expressionFactory->create($expressionName, $options);
        $expression->setMessage($message);

        return (bool)$expression->evaluate($data, $errors);
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
