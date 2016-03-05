<?php

namespace Oro\Bundle\ActionBundle\Model;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\ActionBundle\Exception\ForbiddenActionException;
use Oro\Bundle\ActionBundle\Model\Assembler\AttributeAssembler;
use Oro\Bundle\ActionBundle\Model\Assembler\FormOptionsAssembler;
use Oro\Bundle\ActionBundle\Model\Assembler\OperationActionGroupAssembler;

use Oro\Component\Action\Action\ActionFactory;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\Configurable as ConfigurableAction;
use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\Action\Condition\Configurable as ConfigurableCondition;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Operation
{
    /** @var ActionFactory */
    private $actionFactory;

    /** @var ConditionFactory */
    private $conditionFactory;

    /** @var AttributeAssembler */
    private $attributeAssembler;

    /** @var FormOptionsAssembler */
    private $formOptionsAssembler;

    /** @var OperationActionGroupAssembler */
    private $operationActionGroupAssembler;

    /** @var OperationDefinition */
    private $definition;

    /** @var ActionInterface[] */
    private $actions = [];

    /** @var AbstractCondition */
    private $preconditions;

    /** @var AttributeManager[] */
    private $attributeManagers = [];

    /** @var array */
    private $formOptions;

    /** @var OperationActionGroup[] */
    private $operationActionGroups;

    /**
     * @param ActionFactory $actionFactory
     * @param ConditionFactory $conditionFactory
     * @param AttributeAssembler $attributeAssembler
     * @param FormOptionsAssembler $formOptionsAssembler
     * @param OperationActionGroupAssembler $operationActionGroupAssembler
     * @param OperationDefinition $definition
     */
    public function __construct(
        ActionFactory $actionFactory,
        ConditionFactory $conditionFactory,
        AttributeAssembler $attributeAssembler,
        FormOptionsAssembler $formOptionsAssembler,
        OperationActionGroupAssembler $operationActionGroupAssembler,
        OperationDefinition $definition
    ) {
        $this->actionFactory = $actionFactory;
        $this->conditionFactory = $conditionFactory;
        $this->attributeAssembler = $attributeAssembler;
        $this->formOptionsAssembler = $formOptionsAssembler;
        $this->operationActionGroupAssembler = $operationActionGroupAssembler;
        $this->definition = $definition;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->getDefinition()->isEnabled();
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

    /**
     * @param ActionData $data
     */
    public function init(ActionData $data)
    {
        $this->executeActions($data, OperationDefinition::FORM_INIT);
    }

    /**
     * @param ActionData $data
     * @param Collection $errors
     * @throws ForbiddenActionException
     */
    public function execute(ActionData $data, Collection $errors = null)
    {
        if (!$this->isAllowed($data, $errors)) {
            throw new ForbiddenActionException(sprintf('Action "%s" is not allowed.', $this->getName()));
        }

        throw new ForbiddenActionException('This function does not implemented yet');
        //$this->executeFunctions($data, OperationDefinition::FUNCTIONS);
    }

    /**
     * Check that action is available to show
     *
     * @param ActionData $data
     * @param Collection $errors
     * @return bool
     */
    public function isAvailable(ActionData $data, Collection $errors = null)
    {
        if ($this->hasForm()) {
            return $this->isPreconditionAllowed($data, $errors);
        } else {
            return $this->isAllowed($data, $errors);
        }
    }

    /**
     * Check is action allowed to execute
     *
     * @param ActionData $data
     * @param Collection|null $errors
     * @return bool
     */
    public function isAllowed(ActionData $data, Collection $errors = null)
    {
        throw new ForbiddenActionException('This function does not implemented yet');

        return $this->isPreConditionAllowed($data, $errors) &&
            $this->evaluateConditions($data, OperationDefinition::CONDITIONS, $errors);
    }

    /**
     * @param ActionData $data
     * @param Collection $errors
     * @return bool
     */
    protected function isPreconditionAllowed(ActionData $data, Collection $errors = null)
    {
        $this->executeActions($data, OperationDefinition::PREACTIONS);

        return $this->evaluatePreconditions($data, $errors);
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
     * @param Collection $errors
     * @return boolean
     */
    protected function evaluatePreconditions(ActionData $data, Collection $errors = null)
    {
        $this->preconditions = false;

        $config = $this->definition->getPreconditions();
        if ($config) {
            $this->preconditions = $this->conditionFactory->create(ConfigurableCondition::ALIAS, $config);
        }

        if ($this->preconditions instanceof ConfigurableCondition) {
            return $this->preconditions->evaluate($data, $errors);
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

    /**
     * @return array
     */
    public function getOperationActionGroups()
    {
        if ($this->operationActionGroups === null) {
            $this->operationActionGroups = [];
            $config = $this->definition->getActionGroups();
            if ($config) {
                $this->operationActionGroups = $this->operationActionGroupAssembler->assemble($config);
            }
        }

        return $this->operationActionGroups;
    }
}
