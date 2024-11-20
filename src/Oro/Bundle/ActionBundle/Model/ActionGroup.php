<?php

namespace Oro\Bundle\ActionBundle\Model;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Event\ActionGroupEventDispatcher;
use Oro\Bundle\ActionBundle\Event\ActionGroupExecuteEvent;
use Oro\Bundle\ActionBundle\Event\ActionGroupGuardEvent;
use Oro\Bundle\ActionBundle\Event\ActionGroupPreExecuteEvent;
use Oro\Bundle\ActionBundle\Exception\ForbiddenActionGroupException;
use Oro\Bundle\ActionBundle\Model\ActionGroup\ParametersResolver;
use Oro\Bundle\ActionBundle\Model\Assembler\ParameterAssembler;
use Oro\Component\Action\Action\ActionFactoryInterface;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\Configurable as ConfigurableAction;
use Oro\Component\Action\Condition\Configurable as ConfigurableCondition;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

/**
 * Service that represents ActionGroup created based on YAML definition.
 */
class ActionGroup implements ActionGroupInterface
{
    private ActionFactoryInterface $actionFactory;
    private ConditionFactory $conditionFactory;
    private ParameterAssembler $parameterAssembler;
    private ParametersResolver $parametersResolver;
    private ActionGroupEventDispatcher $eventDispatcher;
    private ActionGroupDefinition $definition;

    /** @var array<string,Parameter>|null */
    private ?array $parameters = null;

    public function __construct(
        ActionFactoryInterface $actionFactory,
        ConditionFactory $conditionFactory,
        ParameterAssembler $parameterAssembler,
        ParametersResolver $parametersResolver,
        ActionGroupEventDispatcher $eventDispatcher,
        ActionGroupDefinition $definition
    ) {
        $this->actionFactory = $actionFactory;
        $this->conditionFactory = $conditionFactory;
        $this->parameterAssembler = $parameterAssembler;
        $this->definition = $definition;
        $this->parametersResolver = $parametersResolver;
        $this->eventDispatcher = $eventDispatcher;
    }

    #[\Override]
    public function execute(ActionData $data, Collection $errors = null): ActionData
    {
        $this->parametersResolver->resolve($data, $this, $errors);

        if (!$this->isAllowed($data, $errors)) {
            throw new ForbiddenActionGroupException(
                sprintf('ActionGroup "%s" is not allowed', $this->definition->getName())
            );
        }

        $preExecuteEvent = new ActionGroupPreExecuteEvent($data, $this->getDefinition(), $errors);
        $this->eventDispatcher->dispatch($preExecuteEvent);

        $this->executeActions($data);

        $executeEvent = new ActionGroupExecuteEvent($data, $this->getDefinition(), $errors);
        $this->eventDispatcher->dispatch($executeEvent);

        return $data;
    }

    #[\Override]
    public function getDefinition(): ActionGroupDefinition
    {
        return $this->definition;
    }

    #[\Override]
    public function isAllowed(ActionData $data, Collection $errors = null): bool
    {
        $guardEvent = new ActionGroupGuardEvent($data, $this->getDefinition(), $errors);
        $this->eventDispatcher->dispatch($guardEvent);

        if (!$guardEvent->isAllowed()) {
            return false;
        }

        if ($config = $this->definition->getConditions()) {
            $conditions = $this->conditionFactory->create(ConfigurableCondition::ALIAS, $config);
            if ($conditions instanceof ConfigurableCondition) {
                return (bool)$conditions->evaluate($data, $errors);
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

    /**
     * @return array<string,Parameter>
     */
    #[\Override]
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
