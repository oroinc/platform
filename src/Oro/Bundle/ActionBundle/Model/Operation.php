<?php

namespace Oro\Bundle\ActionBundle\Model;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Exception\ForbiddenOperationException;
use Oro\Bundle\ActionBundle\Model\Assembler\AttributeAssembler;
use Oro\Bundle\ActionBundle\Model\Assembler\FormOptionsAssembler;
use Oro\Component\Action\Action\ActionFactoryInterface;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\Configurable as ConfigurableAction;
use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\Action\Condition\Configurable as ConfigurableCondition;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

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

    /**
     * @param ActionFactoryInterface $actionFactory
     * @param ConditionFactory $conditionFactory
     * @param AttributeAssembler $attributeAssembler
     * @param FormOptionsAssembler $formOptionsAssembler
     * @param OperationDefinition $definition
     */
    public function __construct(
        ActionFactoryInterface $actionFactory,
        ConditionFactory $conditionFactory,
        AttributeAssembler $attributeAssembler,
        FormOptionsAssembler $formOptionsAssembler,
        OperationDefinition $definition
    ) {
        $this->actionFactory = $actionFactory;
        $this->conditionFactory = $conditionFactory;
        $this->attributeAssembler = $attributeAssembler;
        $this->formOptionsAssembler = $formOptionsAssembler;
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
     * @throws ForbiddenOperationException
     */
    public function execute(ActionData $data, Collection $errors = null)
    {
        if (!$this->isAllowed($data, $errors)) {
            throw new ForbiddenOperationException(sprintf('Operation "%s" is not allowed.', $this->getName()));
        }

        $data['errors'] = $errors;

        $this->executeActions($data, OperationDefinition::ACTIONS);
    }

    /**
     * Check that operation is available to show
     *
     * @param ActionData $data
     * @param Collection $errors
     * @return bool
     */
    public function isAvailable(ActionData $data, Collection $errors = null)
    {
        if ($this->hasForm()) {
            return $this->isPreConditionAllowed($data, $errors);
        } else {
            return $this->isAllowed($data, $errors);
        }
    }

    /**
     * Check is operation allowed to execute
     *
     * @param ActionData $data
     * @param Collection|null $errors
     * @return bool
     */
    protected function isAllowed(ActionData $data, Collection $errors = null)
    {
        return $this->isPreConditionAllowed($data, $errors) &&
            $this->evaluateConditions($data, OperationDefinition::CONDITIONS, $errors);
    }

    /**
     * @param ActionData $data
     * @param Collection $errors
     * @return bool
     */
    protected function isPreConditionAllowed(ActionData $data, Collection $errors = null)
    {
        $this->executeActions($data, OperationDefinition::PREACTIONS);

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
     * @param Collection $errors
     * @return boolean
     */
    protected function evaluateConditions(ActionData $data, $name, Collection $errors = null)
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
}
