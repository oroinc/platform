<?php

namespace Oro\Bundle\ActionBundle\Model;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Event\OperationAnnounceEvent;
use Oro\Bundle\ActionBundle\Event\OperationEventDispatcher;
use Oro\Bundle\ActionBundle\Event\OperationExecuteEvent;
use Oro\Bundle\ActionBundle\Event\OperationGuardEvent;
use Oro\Bundle\ActionBundle\Event\OperationPreExecuteEvent;
use Oro\Bundle\ActionBundle\Exception\ForbiddenOperationException;
use Oro\Bundle\ActionBundle\Model\Assembler\AttributeAssembler;
use Oro\Bundle\ActionBundle\Model\Assembler\FormOptionsAssembler;
use Oro\Bundle\ActionBundle\Resolver\OptionsResolver;
use Oro\Component\Action\Action\ActionFactoryInterface;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\Configurable as ConfigurableAction;
use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\Action\Condition\Configurable as ConfigurableCondition;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

/**
 * Responsible for operation actions
 */
class Operation
{
    /** @var ActionFactoryInterface */
    private $actionFactory;

    /** @var ConditionFactory */
    private $conditionFactory;

    /** @var AttributeAssembler */
    private $attributeAssembler;

    /** @var FormOptionsAssembler */
    private $formOptionsAssembler;

    /** @var OperationDefinition */
    private $definition;

    /** @var ActionInterface[] */
    private $actions = [];

    /** @var AbstractCondition[] */
    private $conditions = [];

    /** @var AttributeManager[] */
    private $attributeManagers = [];

    /** @var array */
    private $formOptions;

    private OptionsResolver $optionsResolver;

    /** @var OperationEventDispatcher */
    protected $eventDispatcher;

    /** @var OperationServiceInterface|null */
    protected $operationService;

    public function __construct(
        ActionFactoryInterface $actionFactory,
        ConditionFactory $conditionFactory,
        AttributeAssembler $attributeAssembler,
        FormOptionsAssembler $formOptionsAssembler,
        OptionsResolver $optionsResolver,
        OperationEventDispatcher $eventDispatcher,
        OperationDefinition $definition
    ) {
        $this->actionFactory = $actionFactory;
        $this->conditionFactory = $conditionFactory;
        $this->attributeAssembler = $attributeAssembler;
        $this->formOptionsAssembler = $formOptionsAssembler;
        $this->optionsResolver = $optionsResolver;
        $this->eventDispatcher = $eventDispatcher;
        $this->definition = $definition;
    }

    public function setOperationService(?OperationServiceInterface $operationService): self
    {
        $this->operationService = $operationService;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->getDefinition()->getEnabled() === true;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getDefinition()->getName();
    }

    /**
     * @return OperationDefinition
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    public function init(ActionData $data)
    {
        $this->executeActions($data, OperationDefinition::FORM_INIT);
    }

    /**
     * @param ActionData $data
     * @param Collection $errors
     * @throws ForbiddenOperationException
     */
    public function execute(ActionData $data, ?Collection $errors = null)
    {
        if (!$this->isAllowed($data, $errors)) {
            throw new ForbiddenOperationException(sprintf('Operation "%s" is not allowed.', $this->getName()));
        }

        $data['errors'] = $errors;

        $preExecuteEvent = new OperationPreExecuteEvent($data, $this->getDefinition(), $errors);
        $this->eventDispatcher->dispatch($preExecuteEvent);
        if ($this->operationService) {
            $this->operationService->execute($data);
        } else {
            $this->executeActions($data, OperationDefinition::ACTIONS);
        }
        $executeEvent = new OperationExecuteEvent($data, $this->getDefinition(), $errors);
        $this->eventDispatcher->dispatch($executeEvent);
    }

    /**
     * Check that operation is available to show
     *
     * @param ActionData $data
     * @return bool
     */
    public function isAvailable(ActionData $data)
    {
        return $this->isPreConditionAllowed($data);
    }

    /**
     * Check is operation allowed to execute
     *
     * @param ActionData $data
     * @param Collection|null $errors
     * @return bool
     */
    public function isAllowed(ActionData $data, ?Collection $errors = null)
    {
        return $this->isPreConditionAllowed($data, $errors)
            && $this->getDefinition()->getEnabled()
            && $this->isConditionAllowed($data, $errors);
    }

    private function isConditionAllowed(ActionData $data, ?Collection $errors = null): bool
    {
        $guardEvent = new OperationGuardEvent($data, $this->getDefinition(), $errors);
        $this->eventDispatcher->dispatch($guardEvent);
        if (!$guardEvent->isAllowed()) {
            return false;
        }

        if ($this->operationService) {
            return $this->operationService->isConditionAllowed($data, $errors);
        }

        return $this->evaluateConditions($data, OperationDefinition::CONDITIONS, $errors);
    }

    /**
     * @param ActionData $data
     * @param Collection|null $errors
     * @return bool
     */
    protected function isPreConditionAllowed(ActionData $data, ?Collection $errors = null)
    {
        $announceEvent = new OperationAnnounceEvent($data, $this->getDefinition(), $errors);
        $this->eventDispatcher->dispatch($announceEvent);
        if (!$announceEvent->isAllowed()) {
            return false;
        }

        if ($this->operationService) {
            $isAllowed = $this->operationService->isPreConditionAllowed($data, $errors);
            $this->resolveDefinitionVariableProperties($data);

            return $isAllowed;
        }

        $this->executeActions($data, OperationDefinition::PREACTIONS);

        $this->resolveDefinitionVariableProperties($data);

        return $this->evaluateConditions($data, OperationDefinition::PRECONDITIONS, $errors);
    }

    /**
     * @param ActionData $data
     * @return AttributeManager
     */
    public function getAttributeManager(ActionData $data)
    {
        $hash = spl_object_hash($data);

        if (!array_key_exists($hash, $this->attributeManagers)) {
            $this->attributeManagers[$hash] = false;

            $config = $this->definition->getAttributes();
            if ($config) {
                $this->attributeManagers[$hash] = new AttributeManager(
                    $this->attributeAssembler->assemble($data, $config)
                );
            }
        }

        return $this->attributeManagers[$hash];
    }

    /**
     * @param ActionData $data
     * @return array
     */
    public function getFormOptions(ActionData $data)
    {
        if ($this->formOptions === null) {
            $this->formOptions = [];
            $formOptionsConfig = $this->definition->getFormOptions();
            if ($formOptionsConfig) {
                $this->formOptions = $this->formOptionsAssembler
                    ->assemble($formOptionsConfig, $this->getAttributeManager($data)->getAttributes());
            }
        }

        return $this->formOptions;
    }

    /**
     * @param ActionData $data
     * @param string $name
     */
    protected function executeActions(ActionData $data, $name)
    {
        if (!array_key_exists($name, $this->actions)) {
            $this->actions[$name] = false;

            $config = $this->definition->getActions($name);
            if ($config) {
                $this->actions[$name] = $this->actionFactory->create(ConfigurableAction::ALIAS, $config);
            }
        }

        if ($this->actions[$name] instanceof ActionInterface) {
            $this->actions[$name]->execute($data);
        }
    }

    /**
     * @param ActionData $data
     * @param string $name
     * @param Collection|null $errors
     * @return boolean
     */
    protected function evaluateConditions(ActionData $data, $name, ?Collection $errors = null)
    {
        if (!array_key_exists($name, $this->conditions)) {
            $this->conditions[$name] = false;

            $config = $this->definition->getConditions($name);
            if ($config) {
                $this->conditions[$name] = $this->conditionFactory->create(ConfigurableCondition::ALIAS, $config);
            }
        }

        if ($this->conditions[$name] instanceof ConfigurableCondition) {
            return $this->conditions[$name]->evaluate($data, $errors);
        }

        return true;
    }

    /**
     * @return bool
     */
    public function hasForm()
    {
        $formOptionsConfig = $this->definition->getFormOptions();

        return !empty($formOptionsConfig['attribute_fields']);
    }

    public function __clone()
    {
        $this->definition = clone $this->getDefinition();
    }

    private function resolveDefinitionVariableProperties(ActionData $actionData): void
    {
        $definition = $this->getDefinition();

        $properties = [
            'enabled' => $definition->getEnabled()
        ];

        $resolvedOptions = $this->optionsResolver->resolveOptions($actionData, $properties);

        $definition
            ->setFrontendOptions(
                $this->optionsResolver->resolveOptions($actionData, $definition->getFrontendOptions())
            )
            ->setButtonOptions(
                $this->optionsResolver->resolveOptions($actionData, $definition->getButtonOptions())
            )
            ->setEnabled($resolvedOptions['enabled']);
    }
}
