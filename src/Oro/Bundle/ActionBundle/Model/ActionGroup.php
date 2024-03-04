<?php

namespace Oro\Bundle\ActionBundle\Model;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Exception\ForbiddenActionGroupException;
use Oro\Bundle\ActionBundle\Model\ActionGroup\ParametersResolver;
use Oro\Bundle\ActionBundle\Model\Assembler\ParameterAssembler;
use Oro\Component\Action\Action\ActionFactoryInterface;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\Configurable as ConfigurableAction;
use Oro\Component\Action\Condition\Configurable as ConfigurableCondition;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

class ActionGroup implements ActionGroupInterface
{
    private ActionFactoryInterface $actionFactory;
    private ConditionFactory $conditionFactory;
    private ParameterAssembler $parameterAssembler;
    private ParametersResolver $parametersResolver;
    private ActionGroupDefinition $definition;

    /** @var array|Parameter[] */
    private ?array $parameters = null;

    public function __construct(
        ActionFactoryInterface $actionFactory,
        ConditionFactory $conditionFactory,
        ParameterAssembler $parameterAssembler,
        ParametersResolver $parametersResolver,
        ActionGroupDefinition $definition
    ) {
        $this->actionFactory = $actionFactory;
        $this->conditionFactory = $conditionFactory;
        $this->parameterAssembler = $parameterAssembler;
        $this->definition = $definition;
        $this->parametersResolver = $parametersResolver;
    }

    public function execute(ActionData $data, Collection $errors = null): ActionData
    {
        $this->parametersResolver->resolve($data, $this, $errors);

        if (!$this->isAllowed($data, $errors)) {
            throw new ForbiddenActionGroupException(
                sprintf('ActionGroup "%s" is not allowed', $this->definition->getName())
            );
        }
        $this->executeActions($data);

        return $data;
    }

    public function getDefinition(): ActionGroupDefinition
    {
        return $this->definition;
    }

    public function isAllowed(ActionData $data, Collection $errors = null): bool
    {
        if ($config = $this->definition->getConditions()) {
            $conditions = $this->conditionFactory->create(ConfigurableCondition::ALIAS, $config);
            if ($conditions instanceof ConfigurableCondition) {
                return $conditions->evaluate($data, $errors);
            }
        }

        return true;
    }

    protected function executeActions(ActionData $data): void
    {
        if ($config = $this->definition->getActions()) {
            $actions = $this->actionFactory->create(ConfigurableAction::ALIAS, $config);
            if ($actions instanceof ActionInterface) {
                $actions->execute($data);
            }
        }
    }

    public function getParameters(): array
    {
        if ($this->parameters === null) {
            $this->parameters = [];
            $parametersConfig = $this->definition->getParameters();
            if ($parametersConfig) {
                $this->parameters = $this->parameterAssembler->assemble($parametersConfig);
            }
        }

        return $this->parameters;
    }
}
