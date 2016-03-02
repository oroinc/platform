<?php

namespace Oro\Bundle\ActionBundle\Model;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\ActionBundle\Exception\ForbiddenActionException;
use Oro\Component\ConfigExpression\Action\ActionFactory as FunctionFactory;
use Oro\Component\ConfigExpression\Action\ActionInterface as FunctionInterface;
use Oro\Component\ConfigExpression\Action\Configurable as ConfigurableAction;
use Oro\Component\ConfigExpression\Condition\AbstractConfigurableCondition;
use Oro\Component\ConfigExpression\Condition\Configurable as ConfigurableCondition;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class Action
{
    /** @var FunctionFactory */
    private $functionFactory;

    /** @var ConditionFactory */
    private $conditionFactory;

    /** @var AttributeAssembler */
    private $attributeAssembler;

    /** @var FormOptionsAssembler */
    private $formOptionsAssembler;

    /** @var ActionDefinition */
    private $definition;

    /**
     * Actions can be substituted by other actions. This variable keeps original action name.
     * @var string
     */
    private $originName;

    /** @var FunctionInterface[] */
    private $functions = [];

    /** @var AbstractConfigurableCondition[] */
    private $conditions = [];

    /** @var AttributeManager[] */
    private $attributeManagers = [];

    /** @var array */
    private $formOptions;

    /**
     * @param FunctionFactory $functionFactory
     * @param ConditionFactory $conditionFactory
     * @param AttributeAssembler $attributeAssembler
     * @param FormOptionsAssembler $formOptionsAssembler
     * @param ActionDefinition $definition
     */
    public function __construct(
        FunctionFactory $functionFactory,
        ConditionFactory $conditionFactory,
        AttributeAssembler $attributeAssembler,
        FormOptionsAssembler $formOptionsAssembler,
        ActionDefinition $definition
    ) {
        $this->functionFactory = $functionFactory;
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
     * @return ActionDefinition
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
        $this->executeFunctions($data, ActionDefinition::FORM_INIT);
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

        $this->executeFunctions($data, ActionDefinition::FUNCTIONS);
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
            return $this->isPreConditionAllowed($data, $errors);
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
        return $this->isPreConditionAllowed($data, $errors) &&
        $this->evaluateConditions($data, ActionDefinition::CONDITIONS, $errors);
    }

    /**
     * @param ActionData $data
     * @param Collection $errors
     * @return bool
     */
    protected function isPreConditionAllowed(ActionData $data, Collection $errors = null)
    {
        $this->executeFunctions($data, ActionDefinition::PREFUNCTIONS);

        return $this->evaluateConditions($data, ActionDefinition::PRECONDITIONS, $errors);
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
    protected function executeFunctions(ActionData $data, $name)
    {
        if (!array_key_exists($name, $this->functions)) {
            $this->functions[$name] = false;

            $config = $this->definition->getFunctions($name);
            if ($config) {
                $this->functions[$name] = $this->functionFactory->create(ConfigurableAction::ALIAS, $config);
            }
        }

        if ($this->functions[$name] instanceof FunctionInterface) {
            $this->functions[$name]->execute($data);
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

    /**
     * @return bool
     */
    public function hasUnboundSubstitution()
    {
        return (
            $this->definition->getSubstituteAction() &&
            false === (
                $this->definition->getGroups() ||
                $this->definition->getDatagrids() ||
                $this->definition->getEntities() ||
                $this->definition->getExcludeEntities() ||
                $this->definition->isForAllEntities()
            )
        );
    }

    /**
     * @return string
     */
    public function getOriginName()
    {
        return $this->originName;
    }

    /**
     * @param string $originName
     * @return $this
     */
    public function setOriginName($originName)
    {
        $this->originName = $originName;
        return $this;
    }
}
