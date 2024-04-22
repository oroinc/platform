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

    public function executeAction(string $actionName, array $data = [], mixed $context = null): mixed
    {
        $action = $this->actionFactory->create($actionName, $context ? $data : $this->prepareOptions($data));

        if (!$context) {
            $context = new ActionData($data);
        }
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
        \ArrayAccess $errors = null,
        string $message = null,
        mixed $context = null
    ): bool {
        $options = $context ? $data : $this->prepareOptions($data);
        $expression = $this->expressionFactory->create($expressionName, $options);
        $expression->setMessage($message);

        if (!$context) {
            $context = $data;
        }

        return $expression->evaluate($context, $errors);
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
